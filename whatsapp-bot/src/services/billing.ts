import pool from '../db/connection';
import axios from 'axios';
import * as logger from './logger';

// Fonction pour charger les plans depuis la base de données
async function getPlans() {
  return await logger.getPlanConfig();
}

export async function incrementConversation(vendor_id: number): Promise<{ suspended: boolean; alert: string | null }> {
  const plans = await getPlans();
  const [rows] = await pool.query(
    'SELECT s.*, v.bot_status FROM subscriptions s JOIN vendors v ON s.vendor_id = v.id WHERE s.vendor_id = ? AND s.status = "active"',
    [vendor_id]
  ) as any[];

  if (rows.length === 0) {
    return { suspended: false, alert: null };
  }

  const subscription = rows[0];

  if (subscription.plan === 'pro') {
    return { suspended: false, alert: null };
  }

  const newCount = subscription.conversations_count + 1;
  let surplusCount = subscription.surplus_count;
  let surplusAmount = subscription.surplus_amount;
  let suspended = false;
  let alert: string | null = null;

  if (subscription.plan === 'gratuit' && newCount >= 50) {
    await pool.query(
      'UPDATE vendors SET bot_status = "suspended" WHERE id = ?',
      [vendor_id]
    );
    suspended = true;
    alert = 'suspended';
  } else if (subscription.plan !== 'gratuit' && newCount > subscription.conversations_included) {
    surplusCount = subscription.surplus_count + 1;
    const planConfig = plans[subscription.plan];
    surplusAmount = Math.ceil(surplusCount / 100) * planConfig.surplus_per_100;

    await pool.query(
      'UPDATE subscriptions SET conversations_count = ?, surplus_count = ?, surplus_amount = ? WHERE vendor_id = ?',
      [newCount, surplusCount, surplusAmount, vendor_id]
    );

    const percentage = (newCount / subscription.conversations_included) * 100;

    if (percentage >= 80 && subscription.alert_80_sent === 0) {
      await pool.query('UPDATE subscriptions SET alert_80_sent = 1 WHERE vendor_id = ?', [vendor_id]);
      alert = '80';
    } else if (percentage >= 100 && subscription.alert_100_sent === 0) {
      await pool.query('UPDATE subscriptions SET alert_100_sent = 1 WHERE vendor_id = ?', [vendor_id]);
      alert = '100';
    } else if (percentage >= 150 && subscription.alert_150_sent === 0) {
      await pool.query('UPDATE subscriptions SET alert_150_sent = 1 WHERE vendor_id = ?', [vendor_id]);
      alert = '150';
    }
  } else {
    await pool.query(
      'UPDATE subscriptions SET conversations_count = ? WHERE vendor_id = ?',
      [newCount, vendor_id]
    );
  }

  return { suspended, alert };
}

export async function getAlertMessage(vendor_id: number, alert: string): Promise<string> {
  const plans = await getPlans();
  const [rows] = await pool.query(
    'SELECT * FROM subscriptions WHERE vendor_id = ? AND status = "active"',
    [vendor_id]
  ) as any[];

  const subscription = rows[0];
  const surplusLink = await generatePaymentLink(vendor_id, 'surplus');

  switch (alert) {
    case '80':
      return `⚠️ Tu approches ta limite de conversations mensuelles (${subscription.conversations_included}).
Ton bot continue de fonctionner normalement.
Si tu dépasses, tu pourras payer le surplus ou changer de plan.`;

    case '100':
      return `Tu as atteint ta limite de conversations mensuelles.
Ton bot continue de fonctionner, mais chaque conversation supplémentaire sera facturée.
Voici le lien pour payer le surplus si tu ne veux pas changer de plan :
${surplusLink}`;

    case '150':
      return `🔴 Tu as un surplus important de conversations (${subscription.surplus_count} au-delà de la limite).
Le montant dû est de ${subscription.surplus_amount} FCFA.
Paiement surplus : ${surplusLink}
Ou passe à un plan supérieur pour éviter les surplus !`;

    case 'suspended':
      const starterLink = await generatePaymentLink(vendor_id, 'starter');
      const standardLink = await generatePaymentLink(vendor_id, 'standard');
      const proLink = await generatePaymentLink(vendor_id, 'pro');

      const starterPrice = plans.starter?.prix || 2500;
      const standardPrice = plans.standard?.prix || 6000;
      const proPrice = plans.pro?.prix || 12000;

      return `Ton bot est suspendu car tu as épuisé ton plan gratuit.
Pour réactiver ton bot, choisis un plan payant :

⭐ STARTER — ${starterPrice} FCFA/mois (500 conversations)
${starterLink}

🚀 STANDARD — ${standardPrice} FCFA/mois (2000 conversations)
${standardLink}

💎 PRO — ${proPrice} FCFA/mois (illimité)
${proLink}`;

    default:
      return '';
  }
}

export async function generatePaymentLink(
  vendor_id: number,
  type: 'starter' | 'standard' | 'pro' | 'surplus',
  amount?: number
): Promise<string> {
  const plans = await getPlans();
  const apiUrl = process.env.MONEROO_API_URL;
  const secretKey = process.env.MONEROO_SECRET_KEY;

  if (!apiUrl || !secretKey) {
    await logger.log('error', 'billing', 'MONEROO_API_URL ou MONEROO_SECRET_KEY non défini');
    return 'Contacte-nous sur WhatsApp pour payer';
  }

  const planConfig = plans[type === 'surplus' ? 'starter' : type];
  const paymentAmount = amount || planConfig.prix;

  try {
    const response = await axios.post(
      `${apiUrl}/payments`,
      {
        amount: paymentAmount,
        currency: 'XOF',
        description: `Abonnement bot ${type.toUpperCase()} - Vendor ${vendor_id}`,
        metadata: {
          vendor_id,
          type,
          plan: type,
        },
      },
      {
        headers: {
          'Authorization': `Bearer ${secretKey}`,
          'Content-Type': 'application/json',
        },
      }
    );

    return response.data.payment_url || 'Contacte-nous sur WhatsApp pour payer';
  } catch (error) {
    await logger.log('error', 'billing', 'Erreur génération lien Moneroo', error);
    return 'Contacte-nous sur WhatsApp pour payer';
  }
}

export async function handlePaymentWebhook(payload: any): Promise<{ vendor_id: number; type: string; success: boolean }> {
  const webhookSecret = process.env.MONEROO_WEBHOOK_SECRET;

  if (!webhookSecret || payload.secret !== webhookSecret) {
    await logger.log('error', 'billing', 'Signature webhook invalide');
    return { vendor_id: 0, type: '', success: false };
  }

  if (payload.status !== 'success') {
    return { vendor_id: 0, type: '', success: false };
  }

  const { vendor_id, type, plan } = payload.metadata;

  if (!vendor_id || !type) {
    await logger.log('error', 'billing', 'Metadata incomplet dans webhook');
    return { vendor_id: 0, type: '', success: false };
  }

  try {
    const plans = await getPlans();
    if (['starter', 'standard', 'pro'].includes(type)) {
      const planConfig = plans[type];
      const dateFin = new Date();
      dateFin.setDate(dateFin.getDate() + 30);

      await pool.query(
        'UPDATE subscriptions SET plan = ?, conversations_count = 0, surplus_count = 0, surplus_amount = 0, date_debut = CURDATE(), date_fin = ?, status = "active", alert_80_sent = 0, alert_100_sent = 0, alert_150_sent = 0, surplus_link_sent = 0 WHERE vendor_id = ?',
        [type, dateFin.toISOString().split('T')[0], vendor_id]
      );

      // Si plan Pro : mettre setup_status à 'pending_number' au lieu d'activer directement
      if (type === 'pro') {
        await pool.query(
          'UPDATE vendors SET setup_status = "pending_number" WHERE id = ?',
          [vendor_id]
        );
        await logger.log('info', 'billing', `Paiement Pro confirmé: ${vendor_id} - en attente numéro dédié`);
      } else {
        await pool.query(
          'UPDATE vendors SET bot_status = "active" WHERE id = ?',
          [vendor_id]
        );
        await logger.log('info', 'billing', `Paiement confirmé: ${vendor_id} plan ${type}`);
      }
    } else if (type === 'surplus') {
      await pool.query(
        'UPDATE subscriptions SET surplus_count = 0, surplus_amount = 0, surplus_link_sent = 1 WHERE vendor_id = ?',
        [vendor_id]
      );
    }

    return { vendor_id, type, success: true };
  } catch (error) {
    await logger.log('error', 'billing', 'Erreur traitement webhook paiement', error);
    return { vendor_id: 0, type: '', success: false };
  }
}

export async function getRenewalMessage(vendor_id: number, daysLeft: number): Promise<string> {
  const plans = await getPlans();
  const [rows] = await pool.query(
    'SELECT * FROM subscriptions WHERE vendor_id = ? AND status = "active"',
    [vendor_id]
  ) as any[];

  if (rows.length === 0) {
    return '';
  }

  const subscription = rows[0];
  const starterLink = await generatePaymentLink(vendor_id, 'starter');
  const standardLink = await generatePaymentLink(vendor_id, 'standard');
  const proLink = await generatePaymentLink(vendor_id, 'pro');

  const starterPrice = plans.starter?.prix || 2500;
  const standardPrice = plans.standard?.prix || 6000;
  const proPrice = plans.pro?.prix || 12000;

  if (daysLeft === 3) {
    return `👋 Salut ! Ton abonnement bot expire dans 3 jours.
Pour continuer à profiter de ton bot sans interruption, pense à le renouveler :

⭐ STARTER — ${starterPrice} FCFA/mois
${starterLink}

🚀 STANDARD — ${standardPrice} FCFA/mois
${standardLink}

💎 PRO — ${proPrice} FCFA/mois
${proLink}`;
  } else if (daysLeft === 0) {
    await pool.query(
      'UPDATE vendors SET bot_status = "suspended" WHERE id = ?',
      [vendor_id]
    );

    return `⚠️ Ton abonnement bot a expiré aujourd'hui.
Ton bot est suspendu jusqu'au renouvellement.
Réactive-le maintenant :

⭐ STARTER — ${starterPrice} FCFA/mois
${starterLink}

🚀 STANDARD — ${standardPrice} FCFA/mois
${standardLink}

💎 PRO — ${proPrice} FCFA/mois
${proLink}`;
  }

  return '';
}
