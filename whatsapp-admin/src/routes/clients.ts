import { Router, Request, Response } from 'express';
import pool from '../db/connection';
import axios from 'axios';

const router = Router();

router.get('/', async (req: Request, res: Response) => {
  try {
    const { plan, status } = req.query;
    let query = 'SELECT v.*, s.plan, s.conversations_count, s.date_fin, s.status as sub_status FROM vendors v LEFT JOIN subscriptions s ON v.id = s.vendor_id AND s.status = "active" WHERE 1=1';
    const params: any[] = [];

    if (plan) {
      query += ' AND s.plan = ?';
      params.push(plan);
    }

    if (status) {
      query += ' AND v.bot_status = ?';
      params.push(status);
    }

    query += ' ORDER BY v.created_at DESC';

    const [rows] = await pool.query(query, params) as any[];

    res.render('clients', { clients: rows, filters: { plan, status } });
  } catch (error) {
    console.error('Erreur clients:', error);
    res.status(500).send('Erreur serveur');
  }
});

router.get('/:id', async (req: Request, res: Response) => {
  try {
    const { id } = req.params;

    const [vendorRows] = await pool.query(
      'SELECT v.*, s.plan, s.conversations_count, s.conversations_included, s.surplus_count, s.surplus_amount, s.date_fin, s.status as sub_status FROM vendors v LEFT JOIN subscriptions s ON v.id = s.vendor_id AND s.status = "active" WHERE v.id = ?',
      [id]
    ) as any[];

    if (vendorRows.length === 0) {
      return res.status(404).send('Client introuvable');
    }

    const vendor = vendorRows[0];

    const [productRows] = await pool.query(
      'SELECT * FROM products WHERE vendor_id = ? AND statut = "actif"',
      [id]
    ) as any[];

    const [subHistoryRows] = await pool.query(
      'SELECT * FROM subscriptions WHERE vendor_id = ? ORDER BY created_at DESC LIMIT 10',
      [id]
    ) as any[];

    res.render('client-detail', { vendor, products: productRows, subHistory: subHistoryRows });
  } catch (error) {
    console.error('Erreur client detail:', error);
    res.status(500).send('Erreur serveur');
  }
});

router.post('/:id/activate', async (req: Request, res: Response) => {
  try {
    const { id } = req.params;
    await pool.query('UPDATE vendors SET bot_status = "active" WHERE id = ?', [id]);
    res.redirect(`/clients/${id}`);
  } catch (error) {
    console.error('Erreur activate:', error);
    res.status(500).send('Erreur serveur');
  }
});

router.post('/:id/suspend', async (req: Request, res: Response) => {
  try {
    const { id } = req.params;
    await pool.query('UPDATE vendors SET bot_status = "suspended" WHERE id = ?', [id]);
    res.redirect(`/clients/${id}`);
  } catch (error) {
    console.error('Erreur suspend:', error);
    res.status(500).send('Erreur serveur');
  }
});

router.post('/:id/change-plan', async (req: Request, res: Response) => {
  try {
    const { id } = req.params;
    const { plan } = req.body;

    const [planRows] = await pool.query(
      'SELECT * FROM plan_config WHERE plan = ?',
      [plan]
    ) as any[];

    if (planRows.length === 0) {
      return res.status(400).send('Plan invalide');
    }

    const planConfig = planRows[0];
    const dateFin = new Date();
    dateFin.setDate(dateFin.getDate() + 30);

    await pool.query(
      'UPDATE subscriptions SET plan = ?, conversations_included = ?, conversations_count = 0, surplus_count = 0, surplus_amount = 0, date_debut = CURDATE(), date_fin = ?, status = "active", alert_80_sent = 0, alert_100_sent = 0, alert_150_sent = 0, surplus_link_sent = 0 WHERE vendor_id = ?',
      [plan, planConfig.conversations, dateFin.toISOString().split('T')[0], id]
    );

    await pool.query('UPDATE vendors SET bot_status = "active" WHERE id = ?', [id]);

    res.redirect(`/clients/${id}`);
  } catch (error) {
    console.error('Erreur change plan:', error);
    res.status(500).send('Erreur serveur');
  }
});

router.post('/:id/offer-month', async (req: Request, res: Response) => {
  try {
    const { id } = req.params;
    const dateFin = new Date();
    dateFin.setDate(dateFin.getDate() + 30);

    await pool.query(
      'UPDATE subscriptions SET date_fin = ?, status = "active" WHERE vendor_id = ?',
      [dateFin.toISOString().split('T')[0], id]
    );

    await pool.query('UPDATE vendors SET bot_status = "active" WHERE id = ?', [id]);

    res.redirect(`/clients/${id}`);
  } catch (error) {
    console.error('Erreur offer month:', error);
    res.status(500).send('Erreur serveur');
  }
});

// Route pour activer le numéro dédié Pro
router.post('/:id/activate-number', async (req: Request, res: Response) => {
  try {
    const { id } = req.params;

    // Vérifier que setup_status = 'pending_code'
    const [vendorRows] = await pool.query(
      'SELECT setup_status FROM vendors WHERE id = ?',
      [id]
    ) as any[];

    if (vendorRows.length === 0) {
      return res.status(404).send('Client introuvable');
    }

    const vendor = vendorRows[0];
    if (vendor.setup_status !== 'pending_code') {
      return res.status(400).send('Statut incorrect pour activation');
    }

    // Mettre setup_status à 'active' et nettoyer meta_verification_code
    await pool.query(
      'UPDATE vendors SET setup_status = "active", meta_verification_code = NULL WHERE id = ?',
      [id]
    );

    // Appeler l'API du bot pour notifier le client
    const botUrl = process.env.BOT_INTERNAL_URL;
    const botApiKey = process.env.BOT_API_KEY;

    if (botUrl && botApiKey) {
      try {
        await axios.post(
          `${botUrl}/api/admin/setup-complete`,
          { vendor_id: parseInt(id) },
          { headers: { 'X-Bot-Api-Key': botApiKey } }
        );
      } catch (apiError) {
        console.error('Erreur appel API bot:', apiError);
        // Continuer quand même, le client sera notifié manuellement si besoin
      }
    }

    res.redirect(`/clients/${id}?message=✅ Numéro activé avec succès`);
  } catch (error) {
    console.error('Erreur activate number:', error);
    res.status(500).send('Erreur serveur');
  }
});

export default router;
