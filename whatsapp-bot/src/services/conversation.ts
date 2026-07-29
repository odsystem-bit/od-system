import pool from '../db/connection';
import axios from 'axios';
import * as billingService from './billing';
import * as mantotaService from './mantota';
import * as scannerService from './scanner';
import * as logger from './logger';
import type { Product } from './scanner';

interface Conversation {
  id: number;
  phone_number: string;
  current_step: string | null;
  collected_data: any;
  client_type: string | null;
  vendor_id: number | null;
  current_vendor_id: number | null;
  vendor_history: any;
  pending_first_message: string | null;
  created_at: Date;
  updated_at: Date;
}

const PLANS = {
  gratuit: { conversations: 50, prix: 0, surplus_per_100: 0 },
  starter: { conversations: 1000, prix: 5000, surplus_per_100: 1500 },
  standard: { conversations: 5000, prix: 12000, surplus_per_100: 1000 },
  pro: { conversations: -1, prix: 25000, surplus_per_100: 0 },
};

const MESSAGES = {
  welcome: `Salut 👋 Je suis Tracy, l'assistante d'OD Système !
Je peux t'aider à :
1️⃣ Créer ta boutique en ligne GRATUITEMENT sur Mantota
2️⃣ Configurer un bot WhatsApp pour gérer tes clients
3️⃣ Je suis déjà client, je veux gérer ma boutique`,

  check_presence: `Tu as déjà un site web ou une page en ligne ?
1️⃣ Oui, j'ai un site (donne-moi le lien)
2️⃣ Non, je n'ai rien encore`,

  scan_site: `Envoie-moi le lien de ton site, je vais l'analyser...`,

  collect_email: `Parfait ! Quelle est ton adresse email ?
(Je t'enverras tes identifiants de connexion)`,

  collect_password: `Choisis un mot de passe pour ton compte Mantota
(minimum 8 caractères)`,

  collect_pays: (pays: string) => `Je vois que tu es au ${pays} !`,

  shop_type: `C'est quoi le type de ta boutique ?
1️⃣ Boutique physique (restaurant, salon, magasin...)
2️⃣ Boutique digitale (ebooks, formations, services...)
3️⃣ Les deux à la fois
4️⃣ Restaurant`,

  collect_localisation: `Envoie-moi la localisation GPS de ta boutique 📍
(ou écris ton adresse textuelle)`,

  collecting_product_category: `Ce produit est dans quelle catégorie ?
1️⃣ Entrée  2️⃣ Plat principal
3️⃣ Dessert  4️⃣ Boisson`,

  boutique_created: (shopName: string, slug: string, email: string) => `🎉 Ta boutique ${shopName} est en ligne !
Lien : https://mantota.com/boutique/${slug}
Identifiants envoyés sur cet email : ${email}

Tu veux que je gère automatiquement tes messages
WhatsApp et convertisse tes visiteurs en clients ?
1️⃣ Oui, voir les offres bot
2️⃣ Non merci, ça me suffit pour l'instant`,

  choose_plan: `Nickel 🙌 Voici nos offres pour ton bot WhatsApp :

GRATUIT : 0 FCFA — 50 conversations/mois
⚠️ Bot limité, pas de relance automatique

⭐ STARTER : 2 500 FCFA/mois — 500 conversations/mois
✅ Bot intelligent 24/7
✅ Catalogue connecté à ta boutique
✅ Relance automatique des prospects

🚀 STANDARD : 6 000 FCFA/mois — 2 000 conversations/mois
✅ Tout le Starter +
✅ Statistiques mensuelles

💎 PRO : 12 000 FCFA/mois — illimité
✅ Conversations illimitées
✅ Tout le Standard +
✅ Priorité support

Une conversation = une session complète
avec un de tes clients, peu importe le nombre de messages.

Tu choisis quelle offre ? (réponds 1, 2, 3 ou 4)`,

  vendor_menu: (name: string) => `Bon retour ${name} 👋 Que veux-tu faire ?
1️⃣ Voir mes produits
2️⃣ Ajouter un produit
3️⃣ Modifier un produit
4️⃣ Désactiver un produit
5️⃣ Voir mes stats ce mois
6️⃣ Renouveler mon abonnement
7️⃣ Mettre à jour depuis mon site`,

  list_products: (products: any[]) => {
    if (products.length === 0) return "Tu n'as pas encore de produits.";
    let msg = "Tes produits actifs :\n";
    products.forEach((p, i) => {
      msg += `${i + 1}. ${p.nom} — ${p.prix} FCFA ✅\n`;
    });
    return msg;
  },

  modify_product_select: `Quel numéro de produit tu veux modifier ?`,

  modify_product_field: `Tu veux changer quoi ?
1️⃣ Nom  2️⃣ Prix  3️⃣ Description  4️⃣ Photo`,

  modify_product_value: `Envoie-moi la nouvelle valeur :`,

  view_stats: (subscription: any) => `📊 Stats de ce mois :
Plan : ${subscription.plan}
Conversations utilisées : ${subscription.conversations_count}/${subscription.conversations_included === -1 ? 'illimité' : subscription.conversations_included}
Surplus : ${subscription.surplus_count} (${subscription.surplus_amount} FCFA)
Abonnement expire le : ${subscription.date_fin}`,

  new_client_name: "Parfait ! C'est quoi le nom de ta boutique ?",

  new_client_products_intro: (shopName: string) => 
    `Super ${shopName} 🎉 Maintenant on va ajouter tes produits
un par un. C'est simple :
👉 Envoie-moi d'abord le NOM de ton premier produit`,

  collecting_product_price: (productName: string) => 
    `Ok ! C'est combien le prix de ${productName} ? (en FCFA)`,

  collecting_product_description: (productName: string) => 
    `Parfait ! Donne-moi une courte description de ${productName}
(2-3 lignes max)`,

  collecting_product_image: (productName: string) => 
    `Tu as une photo de ${productName} ? Envoie-la moi 📸
(ou écris 'passer' si tu n'en as pas maintenant)`,

  ask_more_products: (productName: string) => 
    `${productName} ajouté ✅ Tu as d'autres produits à ajouter ?
1️⃣ Oui, ajouter un autre produit
2️⃣ Non, c'est bon pour l'instant`,

  confirm_payment: (planName: string, price: number) => 
    `Tu as choisi ${planName} à ${price} FCFA/mois 👍
Voici ton lien de paiement sécurisé via Moneroo :
https://pay.moneroo.com/pay/placeholder
Dès que c'est fait, reviens me dire 'J'ai payé' et je
crée ta boutique immédiatement 🚀`,

  payment_confirmed: (shopName: string, slug: string) => 
    `Paiement confirmé ✅ Je crée ta boutique maintenant...
🎉 Ta boutique ${shopName} est en ligne sur Mantota !
Voici ton lien : https://mantota.com/boutique/${slug}
Tes identifiants de connexion t'ont été envoyés par email.
Pour retirer ton argent plus tard, complète juste
ta vérification d'identité (KYC) sur mantota.com — 2 min !`,

  invalid_option: "Je n'ai pas compris, réponds juste avec le chiffre correspondant 😊",
};

export async function getOrCreateConversation(phone: string): Promise<Conversation> {
  const [rows] = await pool.query(
    'SELECT * FROM conversations WHERE phone_number = ?',
    [phone]
  ) as any[];

  if (rows.length > 0) {
    const conversation = rows[0];
    if (typeof conversation.collected_data === 'string') {
      try {
        conversation.collected_data = JSON.parse(conversation.collected_data);
      } catch (e) {
        conversation.collected_data = {};
      }
    }
    return conversation;
  }

  const [result] = await pool.query(
    'INSERT INTO conversations (phone_number, current_step, collected_data, client_type) VALUES (?, ?, ?, ?)',
    [phone, 'welcome', JSON.stringify({}), null]
  ) as any[];

  return {
    id: result.insertId,
    phone_number: phone,
    current_step: 'welcome',
    collected_data: {},
    client_type: null,
    vendor_id: null,
    current_vendor_id: null,
    vendor_history: null,
    pending_first_message: null,
    created_at: new Date(),
    updated_at: new Date(),
  };
}

export async function updateConversation(phone: string, step: string, data: {
  collected_data?: any,
  current_vendor_id?: number | null,
  vendor_history?: string | null,
  client_type?: string,
  pending_first_message?: string | null
}): Promise<void> {
  // Construire la requête UPDATE dynamiquement selon les clés présentes
  const updates: string[] = ['current_step = ?', 'updated_at = NOW()'];
  const values: any[] = [step];

  if (data.collected_data !== undefined) {
    updates.push('collected_data = ?');
    values.push(JSON.stringify(data.collected_data));
  }
  if (data.current_vendor_id !== undefined) {
    updates.push('current_vendor_id = ?');
    values.push(data.current_vendor_id);
  }
  if (data.vendor_history !== undefined) {
    updates.push('vendor_history = ?');
    values.push(data.vendor_history);
  }
  if (data.client_type !== undefined) {
    updates.push('client_type = ?');
    values.push(data.client_type);
  }
  if (data.pending_first_message !== undefined) {
    updates.push('pending_first_message = ?');
    values.push(data.pending_first_message);
  }

  values.push(phone);

  await pool.query(
    `UPDATE conversations SET ${updates.join(', ')} WHERE phone_number = ?`,
    values
  );
}

export async function resetConversation(phone: string): Promise<void> {
  await pool.query(
    'UPDATE conversations SET current_step = ?, collected_data = ?, updated_at = NOW() WHERE phone_number = ?',
    ['welcome', JSON.stringify({}), phone]
  );
}

// Fonction pour récupérer un vendor par son code court
export async function getVendorByShortCode(code: string): Promise<any | null> {
  const [rows] = await pool.query(
    'SELECT * FROM vendors WHERE UPPER(short_code) = UPPER(?) AND bot_status = "active"',
    [code]
  ) as any[];
  return rows.length > 0 ? rows[0] : null;
}

// Fonction pour récupérer un vendor par son ID
export async function getVendorById(id: number): Promise<any | null> {
  const [rows] = await pool.query(
    'SELECT * FROM vendors WHERE id = ?',
    [id]
  ) as any[];
  return rows.length > 0 ? rows[0] : null;
}

// Fonction pour charger les produits d'un vendor
export async function loadVendorProducts(vendorId: number): Promise<any[]> {
  const [rows] = await pool.query(
    'SELECT * FROM products WHERE vendor_id = ? AND statut = "actif"',
    [vendorId]
  ) as any[];
  return rows;
}

// Fonction pour ajouter un prospect s'il n'existe pas déjà
export async function addProspectIfNotExists(phone: string, vendorId: number, shortCode: string): Promise<void> {
  await pool.query(
    'INSERT INTO prospects_relance (phone_number, short_code, vendor_id, last_interaction_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE last_interaction_at = NOW()',
    [phone, shortCode, vendorId]
  );
}

async function callGPTMini(message: string, customSystemPrompt?: string): Promise<string> {
  const apiKey = process.env.OPENAI_API_KEY;
  if (!apiKey) {
    return "Désolé, je n'arrive pas à comprendre. Peux-tu reformuler ?";
  }

  try {
    const response = await axios.post(
      'https://api.openai.com/v1/chat/completions',
      {
        model: 'gpt-4o-mini',
        messages: [
          {
            role: 'system',
            content: customSystemPrompt || "Tu es Tracy, l'assistante commerciale d'OD Système (Bénin). Tu aides les gens à créer leur boutique en ligne sur Mantota et à obtenir leur propre bot WhatsApp commercial. Réponds toujours en français informel, sois chaleureux et pousse toujours vers l'action (créer une boutique, choisir un plan, payer). Ne parle jamais de concurrents. Si la question n'a rien à voir avec nos services, redirige poliment vers nos offres."
          },
          {
            role: 'user',
            content: message
          }
        ],
        max_tokens: 300,
      },
      {
        headers: {
          '_authorization': `Bearer ${apiKey}`,
          'Content-Type': 'application/json',
        },
      }
    );

    return response.data.choices[0]?.message?.content || "Désolé, je n'arrive pas à comprendre. Peux-tu reformuler ?";
  } catch (error) {
    console.error('Erreur GPT:', error);
    return "Désolé, j'ai un petit souci technique. Réessaie dans quelques instants 😊";
  }
}

function detectCountry(phone: string): string {
  if (phone.startsWith('+229')) return 'Bénin';
  if (phone.startsWith('+225')) return 'Côte d\'Ivoire';
  if (phone.startsWith('+221')) return 'Sénégal';
  if (phone.startsWith('+237')) return 'Cameroun';
  return 'Afrique de l\'Ouest';
}

export async function processMessage(phone: string, message: string, messageType: string, imageId: string | null = null): Promise<string[]> {
  const lowerMessage = message.toLowerCase().trim();

  // Priorité absolue : menu et recommencer
  if (lowerMessage === 'menu' || lowerMessage === 'recommencer') {
    await resetConversation(phone);
    return [MESSAGES.welcome];
  }

  // BLOC 1 — Détection code boutique (avant tout le reste)
  const upperMessage = message.trim().toUpperCase();
  const conv = await getOrCreateConversation(phone);

  // Vérifier si le message est un code boutique (court, pas d'espaces, 3-8 caractères)
  const looksLikeCode = /^[A-Z]{2}\d{3,6}$/.test(upperMessage);

  if (looksLikeCode) {
    const targetVendor = await getVendorByShortCode(upperMessage);
    
    if (targetVendor) {
      // C'est un code boutique valide
      
      // 1. Récupérer l'historique existant
      let history = conv.vendor_history;
      if (typeof history === 'string') {
        try {
          history = JSON.parse(history);
        } catch {
          history = [];
        }
      } else {
        history = [];
      }
      
      // 2. Ajouter cette boutique à l'historique
      const newVisit = {
        vendor_id: targetVendor.id,
        shop_name: targetVendor.shop_name,
        short_code: targetVendor.short_code,
        visited_at: new Date().toISOString()
      };
      history.push(newVisit);
      
      // 3. Mettre à jour la conversation
      await updateConversation(phone, 'vendor_customer_welcome', {
        current_vendor_id: targetVendor.id,
        vendor_history: JSON.stringify(history),
        client_type: 'end_customer',
        pending_first_message: null
      });
      
      // 4. Incrémenter les conversations du vendeur
      await billingService.incrementConversation(targetVendor.id);
      
      // 5. Vérifier si client est déjà venu dans cette boutique
      const previousVisits = history.filter((v: any) => v.vendor_id === targetVendor.id).length;
      
      if (previousVisits > 1) {
        // Client de retour
        return [
          `Bon retour chez ${targetVendor.shop_name} ! 👋`,
          `Comment puis-je vous aider aujourd'hui ?`
        ];
      } else {
        // Première visite dans cette boutique
        return [
          `Bonjour ! Bienvenue chez ${targetVendor.shop_name} 🎉`,
          `Je suis l'assistant de ${targetVendor.shop_name}.\nComment puis-je vous aider ?`
        ];
      }
    }
    
    // Code non reconnu → traiter comme message normal (continuer le flux)
  }

  // Vérifier si c'est un vendeur connu
  const [vendorRows] = await pool.query(
    'SELECT * FROM vendors WHERE phone_number = ? AND bot_status = "active"',
    [phone]
  ) as any[];

  const isVendor = vendorRows.length > 0;
  const vendor = isVendor ? vendorRows[0] : null;

  const conversation = await getOrCreateConversation(phone);
  const currentStep = conversation.current_step || 'welcome';
  const collectedData = conversation.collected_data || {};

  // Règle 2 : Si en mode vendor_customer_welcome et menu/recommencer
  if (currentStep === 'vendor_customer_welcome' && (lowerMessage === 'menu' || lowerMessage === 'recommencer')) {
    await resetConversation(phone);
    return ["Ok ! Je te ramène au menu principal de Tracy 👋", MESSAGES.welcome];
  }

  // Définir client_type
  if (isVendor && conversation.client_type !== 'vendor_management') {
    conversation.client_type = 'vendor_management';
    await pool.query(
      'UPDATE conversations SET client_type = ?, vendor_id = ? WHERE phone_number = ?',
      ['vendor_management', vendor.id, phone]
    );
  }

  // Incrémenter conversation count et vérifier alertes (pour vendeurs et clients finaux)
  if (isVendor && vendor) {
    const { suspended, alert } = await billingService.incrementConversation(vendor.id);
    if (suspended) {
      return ["⚠️ Ton bot est suspendu. Contacte le support pour réactiver."];
    }
    if (alert) {
      const alertMessage = await billingService.getAlertMessage(vendor.id, alert);
      return [alertMessage];
    }
  } else if (conversation.vendor_id) {
    // Client final d'un vendeur - incrémenter aussi
    const { suspended, alert } = await billingService.incrementConversation(conversation.vendor_id);
    if (suspended) {
      return ["⚠️ Le bot de ce vendeur est suspendu."];
    }
    if (alert) {
      const alertMessage = await billingService.getAlertMessage(conversation.vendor_id, alert);
      // Envoyer l'alerte au vendeur (pas au client)
      const [vendorRows] = await pool.query(
        'SELECT phone_number FROM vendors WHERE id = ?',
        [conversation.vendor_id]
      ) as any[];
      if (vendorRows.length > 0) {
        const { sendMessage } = await import('./whatsapp');
        await sendMessage(vendorRows[0].phone_number, alertMessage);
      }
    }
  }

  switch (currentStep) {
    case 'welcome':
      if (message === '1' || message === '2') {
        await updateConversation(phone, 'check_presence', collectedData);
        return [MESSAGES.check_presence];
      } else if (message === '3') {
        if (isVendor) {
          await updateConversation(phone, 'vendor_menu', collectedData);
          return [MESSAGES.vendor_menu(vendor.name)];
        } else {
          const gptResponse = await callGPTMini(message);
          return [gptResponse];
        }
      } else {
        const gptResponse = await callGPTMini(message);
        await updateConversation(phone, 'welcome', collectedData);
        return [gptResponse, MESSAGES.welcome];
      }

    case 'check_presence':
      if (message === '1') {
        await updateConversation(phone, 'scan_site', collectedData);
        return [MESSAGES.scan_site];
      } else if (message === '2') {
        await updateConversation(phone, 'collect_email', collectedData);
        return [MESSAGES.collect_email];
      } else {
        return [MESSAGES.check_presence, MESSAGES.invalid_option];
      }

    case 'scan_site':
      collectedData.site_url = message;
      try {
        const scannedProducts = await scannerService.scanSite(message);
        collectedData.scanned_products = scannedProducts;
        
        let productList = '';
        scannedProducts.slice(0, 5).forEach((p: any, i: number) => {
          productList += `${i + 1}. ${p.nom} — ${p.prix} FCFA\n`;
        });
        
        await updateConversation(phone, 'collect_email', { ...collectedData, scanned_confirmed: false });
        return [
          `J'ai trouvé ${scannedProducts.length} produits :\n${productList}\nC'est correct ?`,
          '1️⃣ Oui',
          '2️⃣ Non, je corrige'
        ];
      } catch (error) {
        await updateConversation(phone, 'collect_email', collectedData);
        return ["Je n'ai pas pu analyser le site. On continue manuellement !", MESSAGES.collect_email];
      }

    case 'collect_email':
      if (message === '2' && collectedData.scanned_confirmed === false) {
        await updateConversation(phone, 'collect_email', { ...collectedData, scanned_products: [] });
        return [MESSAGES.collect_email];
      }
      
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(message)) {
        return ["Email invalide. Envoie une adresse email valide.", MESSAGES.collect_email];
      }
      
      collectedData.email = message;
      const pays = detectCountry(phone);
      collectedData.pays = pays;
      
      await updateConversation(phone, 'collect_password', collectedData);
      return [MESSAGES.collect_pays(pays), MESSAGES.collect_password];

    case 'collect_password':
      if (message.length < 8) {
        return ["Le mot de passe doit avoir au moins 8 caractères.", MESSAGES.collect_password];
      }
      collectedData.password = message;
      await updateConversation(phone, 'shop_type', collectedData);
      return [MESSAGES.shop_type];

    case 'shop_type':
      const shopTypes = { '1': 'physique', '2': 'digital', '3': 'les_deux', '4': 'restaurant' };
      if (shopTypes[message as keyof typeof shopTypes]) {
        collectedData.shop_type = shopTypes[message as keyof typeof shopTypes];
        
        if (message === '4' || message === '1') {
          await updateConversation(phone, 'collect_localisation', collectedData);
          return [MESSAGES.collect_localisation];
        } else {
          await updateConversation(phone, 'new_client_name', collectedData);
          return [MESSAGES.new_client_name];
        }
      } else {
        return [MESSAGES.shop_type, MESSAGES.invalid_option];
      }

    case 'collect_localisation':
      if (messageType === 'location' && message) {
        // Message de location WhatsApp (format JSON)
        try {
          const locationData = JSON.parse(message);
          collectedData.localisation_lat = locationData.latitude;
          collectedData.localisation_lng = locationData.longitude;
          collectedData.localisation = `${locationData.latitude}, ${locationData.longitude}`;
        } catch {
          collectedData.localisation = message;
        }
      } else {
        collectedData.localisation = message;
      }
      await updateConversation(phone, 'new_client_name', collectedData);
      return [MESSAGES.new_client_name];

    case 'new_client_name':
      collectedData.shop_name = message;
      await updateConversation(phone, 'new_client_products_intro', collectedData);
      return [MESSAGES.new_client_products_intro(message)];

    case 'new_client_products_intro':
      collectedData.current_product = { nom: message };
      await updateConversation(phone, 'collecting_product_price', collectedData);
      return [MESSAGES.collecting_product_price(message)];

    case 'collecting_product_price':
      const price = parseInt(message.replace(/\D/g, ''));
      if (isNaN(price) || price < 100) {
        return [MESSAGES.collecting_product_price(collectedData.current_product?.nom || 'le produit'), "Le prix doit être un nombre valide en FCFA (min 100 FCFA)"];
      }
      collectedData.current_product.prix = price;
      
      if (collectedData.shop_type === 'restaurant') {
        await updateConversation(phone, 'collecting_product_category', collectedData);
        return [MESSAGES.collecting_product_category];
      } else {
        await updateConversation(phone, 'collecting_product_description', collectedData);
        return [MESSAGES.collecting_product_description(collectedData.current_product.nom)];
      }

    case 'collecting_product_category':
      const categories = { '1': 'entrée', '2': 'plat', '3': 'dessert', '4': 'boisson' };
      if (categories[message as keyof typeof categories]) {
        collectedData.current_product.categorie = categories[message as keyof typeof categories];
        await updateConversation(phone, 'collecting_product_description', collectedData);
        return [MESSAGES.collecting_product_description(collectedData.current_product.nom)];
      } else {
        return [MESSAGES.collecting_product_category, MESSAGES.invalid_option];
      }

    case 'collecting_product_description':
      collectedData.current_product.description = message;
      await updateConversation(phone, 'collecting_product_image', collectedData);
      return [MESSAGES.collecting_product_image(collectedData.current_product.nom)];

    case 'collecting_product_image':
      if (messageType === 'image' && imageId) {
        collectedData.current_product.image_url = imageId;
      } else if (message.toLowerCase() === 'passer') {
        collectedData.current_product.image_url = null;
      }
      
      if (!collectedData.products) {
        collectedData.products = [];
      }
      collectedData.products.push({ ...collectedData.current_product });
      delete collectedData.current_product;

      await updateConversation(phone, 'ask_more_products', collectedData);
      return [MESSAGES.ask_more_products(collectedData.products[collectedData.products.length - 1].nom)];

    case 'ask_more_products':
      if (message === '1') {
        await updateConversation(phone, 'new_client_products_intro', collectedData);
        return ["Ok ! Envoie-moi le nom du prochain produit"];
      } else if (message === '2') {
        // Créer la boutique sur Mantota
        try {
          const mantotaResult = await mantotaService.createVendor({
            name: collectedData.shop_name,
            email: collectedData.email,
            password: collectedData.password,
            phone: phone,
            shop_name: collectedData.shop_name,
            shop_type: collectedData.shop_type,
            shop_address: collectedData.localisation,
          });
          
          collectedData.mantota_vendor_id = mantotaResult.id;
          collectedData.slug = mantotaResult.slug;
          collectedData.short_code = mantotaResult.short_code;
          
          await updateConversation(phone, 'boutique_created', collectedData);
          
          const tracyNumber = process.env.WHATSAPP_TRACY_NUMBER || '';
          const whatsappLink = tracyNumber ? `https://wa.me/${tracyNumber.replace('+', '')}?text=${mantotaResult.short_code}` : '';
          
          return [
            `🎉 Ta boutique ${collectedData.shop_name} est en ligne !\nLien : https://mantota.com/boutique/${mantotaResult.slug}\nIdentifiants envoyés à : ${collectedData.email}`,
            `🔗 Ton lien WhatsApp personnel à partager partout (Instagram, Facebook, site web, cartes de visite) :\n\n${whatsappLink}\n\nQuand tes clients cliquent ce lien, WhatsApp s'ouvre et je réponds automatiquement en ton nom 🤖`,
            `🔑 Ton code boutique : ${mantotaResult.short_code}\n(Garde-le précieusement — tes clients peuvent aussi juste envoyer ce code pour te retrouver)`,
            "Tu veux que je gère tes messages et convertisse tes visiteurs en clients automatiquement ? Voici nos offres :"
          ];
        } catch (error) {
          console.error('Erreur création Mantota:', error);
          return ["Désolé, erreur lors de la création. Réessaie plus tard."];
        }
      } else {
        return [MESSAGES.ask_more_products(collectedData.products?.[collectedData.products.length - 1]?.nom || 'le produit'), MESSAGES.invalid_option];
      }

    case 'boutique_created':
      if (message === '1') {
        await updateConversation(phone, 'choose_plan', collectedData);
        return [MESSAGES.choose_plan];
      } else if (message === '2') {
        await resetConversation(phone);
        return ["Parfait ! Reviens quand tu veux. 👋"];
      } else {
        return [MESSAGES.boutique_created(collectedData.shop_name, collectedData.slug, collectedData.email), MESSAGES.invalid_option];
      }

    case 'choose_plan':
      const planKeys = { '1': 'gratuit', '2': 'starter', '3': 'standard', '4': 'pro' };
      if (planKeys[message as keyof typeof planKeys]) {
        const planKey = planKeys[message as keyof typeof planKeys];
        const plan = PLANS[planKey as keyof typeof PLANS];
        collectedData.plan = planKey;
        
        if (planKey === 'gratuit') {
          // Créer subscription gratuite
          const dateFin = new Date();
          dateFin.setDate(dateFin.getDate() + 30);
          
          await pool.query(
            'INSERT INTO subscriptions (vendor_id, plan, conversations_included, date_debut, date_fin) VALUES (?, ?, ?, CURDATE(), ?)',
            [collectedData.mantota_vendor_id, 'gratuit', 50, dateFin.toISOString().split('T')[0]]
          );
          
          await resetConversation(phone);
          return ["✅ Boutique créée avec plan gratuit ! Tu as 50 conversations/mois."];
        } else {
          await updateConversation(phone, 'confirm_payment', collectedData);
          return [MESSAGES.confirm_payment(planKey.toUpperCase(), plan.prix)];
        }
      } else {
        return [MESSAGES.choose_plan, MESSAGES.invalid_option];
      }

    case 'confirm_payment':
      if (lowerMessage.includes('payé') || lowerMessage.includes('paye') || lowerMessage.includes('ok')) {
        // Créer subscription payante
        const dateFin = new Date();
        dateFin.setDate(dateFin.getDate() + 30);
        
        await pool.query(
          'INSERT INTO subscriptions (vendor_id, plan, conversations_included, date_debut, date_fin) VALUES (?, ?, ?, CURDATE(), ?)',
          [collectedData.mantota_vendor_id, collectedData.plan, PLANS[collectedData.plan as keyof typeof PLANS].conversations, dateFin.toISOString().split('T')[0]]
        );
        
        // Si plan Pro : déclencher le flux de configuration numéro dédié
        if (collectedData.plan === 'pro') {
          await pool.query(
            'UPDATE vendors SET setup_status = "pending_number" WHERE id = ?',
            [collectedData.mantota_vendor_id]
          );
          await updateConversation(phone, 'pro_number_request', collectedData);
          return [
            "🎉 Paiement confirmé ! Bienvenue dans le plan Pro 💎\n\nCe plan inclut ton propre numéro WhatsApp dédié à ta boutique — tes clients t'écriront directement à TON numéro.\n\nPour ça, j'ai besoin d'un numéro qui n'a JAMAIS été utilisé sur WhatsApp :\n✅ Une nouvelle SIM MTN/Moov/Orange\n✅ Un numéro que tu n'utilises pas du tout\n❌ PAS ton numéro actuel\n❌ PAS un numéro déjà sur WhatsApp\n\nEnvoie-moi ce numéro avec l'indicatif (ex: +22901XXXXXXXX ou +22500XXXXXXXX)"
          ];
        }
        
        // Pour les autres plans : activation standard
        await pool.query(
          'UPDATE vendors SET bot_status = "active" WHERE id = ?',
          [collectedData.mantota_vendor_id]
        );
        
        await resetConversation(phone);
        return ["✅ Paiement confirmé ! Ton bot est activé. 🎉"];
      } else {
        const plan = PLANS[collectedData.plan as keyof typeof PLANS];
        return [MESSAGES.confirm_payment(collectedData.plan?.toUpperCase() || 'le plan', plan?.prix || 0), "Dis-moi 'J'ai payé' quand c'est fait 😊"];
      }

    case 'vendor_menu':
      if (message === '1') {
        const products = await mantotaService.getVendorProducts(vendor.id);
        await updateConversation(phone, 'list_products', collectedData);
        return [MESSAGES.list_products(products)];
      } else if (message === '2') {
        await updateConversation(phone, 'new_client_products_intro', collectedData);
        return ["Envoie-moi le nom du nouveau produit"];
      } else if (message === '3') {
        const products = await mantotaService.getVendorProducts(vendor.id);
        await updateConversation(phone, 'modify_product_select', { ...collectedData, products_list: products });
        return [MESSAGES.list_products(products), MESSAGES.modify_product_select];
      } else if (message === '4') {
        return ["Pour désactiver un produit, utilise le dashboard sur mantota.com"];
      } else if (message === '5') {
        // Vérifier si le vendeur a un mantota_vendor_id
        const [vendorRows] = await pool.query(
          'SELECT mantota_vendor_id FROM vendors WHERE id = ?',
          [vendor.id]
        ) as any[];
        
        const mantotaVendorId = vendorRows[0]?.mantota_vendor_id;
        
        if (!mantotaVendorId) {
          return ["Tes stats seront disponibles une fois ta boutique Mantota configurée 🏪"];
        }
        
        // Récupérer les stats depuis Mantota
        const stats = await mantotaService.getVendorStats(mantotaVendorId);
        
        if (!stats) {
          return ["Impossible de récupérer tes stats pour le moment. Réessaie plus tard."];
        }
        
        const statsMessage = `📊 Tes stats de ce mois :

📦 Commandes : ${stats.this_month.total_orders}
  ✅ Livrées : ${stats.this_month.delivered}
  🚚 En route : ${stats.this_month.shipped}
  ⏳ En attente : ${stats.this_month.pending}
  🔴 Litiges : ${stats.this_month.disputed}

💰 Revenus :
  Disponible : ${stats.revenue.available} FCFA
  En escrow : ${stats.revenue.in_escrow} FCFA
  Gagné ce mois : ${stats.revenue.earned_this_month} FCFA

🏆 Top produits :
  ${stats.top_products.map((p, i) => `${i + 1}. ${p.name} — ${p.orders} ventes`).join('\n  ')}`;
        
        return [statsMessage];
      } else if (message === '6') {
        return ["Pour renouveler, utilise le dashboard sur mantota.com"];
      } else if (message === '7') {
        if (!vendor.site_externe) {
          return ["Tu n'as pas de site externe configuré. Contacte le support pour en ajouter un."];
        }
        await updateConversation(phone, 'rescan_site', collectedData);
        return ["Je vais scanner ton site pour détecter les changements..."];
      } else {
        return [MESSAGES.vendor_menu(vendor.name), MESSAGES.invalid_option];
      }

    case 'list_products':
      await updateConversation(phone, 'vendor_menu', collectedData);
      return [MESSAGES.vendor_menu(vendor.name)];

    case 'modify_product_select':
      const productIndex = parseInt(message) - 1;
      if (collectedData.products_list && collectedData.products_list[productIndex]) {
        collectedData.selected_product = collectedData.products_list[productIndex];
        await updateConversation(phone, 'modify_product_field', collectedData);
        return [MESSAGES.modify_product_field];
      } else {
        return [MESSAGES.modify_product_select, MESSAGES.invalid_option];
      }

    case 'modify_product_field':
      const fields = { '1': 'nom', '2': 'prix', '3': 'description', '4': 'image_url' };
      if (fields[message as keyof typeof fields]) {
        collectedData.field_to_modify = fields[message as keyof typeof fields];
        await updateConversation(phone, 'modify_product_value', collectedData);
        return [MESSAGES.modify_product_value];
      } else {
        return [MESSAGES.modify_product_field, MESSAGES.invalid_option];
      }

    case 'modify_product_value':
      const field = collectedData.field_to_modify;
      let updateData: any = {};
      
      if (field === 'prix') {
        const newPrice = parseInt(message.replace(/\D/g, ''));
        if (isNaN(newPrice) || newPrice < 100) {
          return ["Prix invalide. Envoie un nombre valide.", MESSAGES.modify_product_value];
        }
        updateData.prix = newPrice;
      } else {
        updateData[field] = message;
      }
      
      try {
        await mantotaService.updateProduct(collectedData.selected_product.id, updateData);
        await updateConversation(phone, 'vendor_menu', collectedData);
        return ["Produit modifié ✅", MESSAGES.vendor_menu(vendor.name)];
      } catch (error) {
        return ["Erreur lors de la modification. Réessaie."];
      }

    case 'rescan_site':
      try {
        // Charger les produits actuels depuis la table products
        const [productRows] = await pool.query(
          'SELECT nom, prix, description, categorie FROM products WHERE vendor_id = ? AND statut = "actif"',
          [vendor.id]
        ) as any[];

        const currentProducts = productRows.map((p: any) => ({
          nom: p.nom,
          prix: p.prix,
          description: p.description,
          categorie: p.categorie,
        }));

        // Scanner le site externe
        const newProducts = await scannerService.scanSite(vendor.site_externe);

        // Comparer les produits
        const compareResult = scannerService.compareProducts(currentProducts, newProducts);

        if (compareResult.hasChanges) {
          // Stocker les nouveaux produits scannés dans vendors_pending
          await pool.query(
            'INSERT INTO vendors_pending (phone_number, name, shop_type, scanned_products_json, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE scanned_products_json = ?, status = ?',
            [phone, vendor.name, vendor.shop_type, JSON.stringify(newProducts), 'ready', JSON.stringify(newProducts), 'ready']
          );

          // Formater et envoyer le message
          const message = scannerService.formatChangesMessage(compareResult);
          await updateConversation(phone, 'confirm_site_update', collectedData);
          return [message];
        } else {
          await updateConversation(phone, 'vendor_menu', collectedData);
          return ["✅ Catalogue déjà à jour !", MESSAGES.vendor_menu(vendor.name)];
        }
      } catch (error) {
        console.error('Erreur rescan site:', error);
        return ["Erreur lors du scan. Réessaie plus tard."];
      }

    case 'confirm_site_update':
      if (message === '1') {
        // Oui, mettre à jour automatiquement
        try {
          // Récupérer les produits scannés depuis vendors_pending
          const [pendingRows] = await pool.query(
            'SELECT scanned_products_json FROM vendors_pending WHERE phone_number = ? AND status = "ready"',
            [phone]
          ) as any[];

          if (pendingRows.length === 0) {
            await resetConversation(phone);
            return ["Désolé, les données scannées ont expiré. Réessaie plus tard."];
          }

          const scannedProducts = JSON.parse(pendingRows[0].scanned_products_json);

          // Récupérer le vendor_id
          const [vendorRows] = await pool.query(
            'SELECT id FROM vendors WHERE phone_number = ?',
            [phone]
          ) as any[];

          if (vendorRows.length === 0) {
            await resetConversation(phone);
            return ["Erreur vendeur introuvable."];
          }

          const vendorId = vendorRows[0].id;

          // Mettre à jour les produits dans la table products
          // D'abord désactiver tous les produits actuels
          await pool.query(
            'UPDATE products SET statut = "inactif" WHERE vendor_id = ?',
            [vendorId]
          );

          // Ajouter ou réactiver les nouveaux produits
          for (const product of scannedProducts) {
            // Vérifier si le produit existe déjà
            const [existingRows] = await pool.query(
              'SELECT id FROM products WHERE vendor_id = ? AND nom = ?',
              [vendorId, product.nom]
            ) as any[];

            if (existingRows.length > 0) {
              // Mettre à jour le produit existant
              await pool.query(
                'UPDATE products SET prix = ?, description = ?, categorie = ?, statut = "actif" WHERE id = ?',
                [product.prix, product.description, product.categorie, existingRows[0].id]
              );
            } else {
              // Créer un nouveau produit
              await pool.query(
                'INSERT INTO products (vendor_id, nom, prix, description, categorie, statut) VALUES (?, ?, ?, ?, ?, "actif")',
                [vendorId, product.nom, product.prix, product.description, product.categorie]
              );
            }
          }

          // Nettoyer vendors_pending
          await pool.query(
            'DELETE FROM vendors_pending WHERE phone_number = ?',
            [phone]
          );

          await resetConversation(phone);
          return ["✅ Catalogue mis à jour automatiquement ! Tes produits sont synchronisés avec ton site."];
        } catch (error) {
          console.error('Erreur mise à jour site:', error);
          return ["Erreur lors de la mise à jour. Réessaie plus tard."];
        }
      } else if (message === '2') {
        // Non, laisser comme c'est
        await pool.query(
          'DELETE FROM vendors_pending WHERE phone_number = ?',
          [phone]
        );
        await resetConversation(phone);
        return ["Ok, je laisse ton catalogue comme il est. 👋"];
      } else {
        return ["Réponds 1 pour mettre à jour ou 2 pour laisser comme c'est"];
      }

    case 'pro_number_request':
      // Valider le numéro de téléphone international
      const phoneRegex = /^\+[1-9]\d{7,14}$/;
      if (!phoneRegex.test(message)) {
        return ["Ce numéro ne semble pas correct. Envoie-le avec l'indicatif pays (ex: +22901XXXXXXXX) 😊"];
      }
      
      // Stocker le numéro et mettre à jour le statut
      await pool.query(
        'UPDATE vendors SET whatsapp_number = ?, setup_status = "pending_number" WHERE id = ?',
        [message, collectedData.mantota_vendor_id]
      );
      
      // Logger
      await logger.log('info', 'setup', `Nouveau numéro Pro reçu: ${message} pour vendor: ${collectedData.mantota_vendor_id}`);
      
      // Alerter l'admin
      const [vendorInfo] = await pool.query(
        'SELECT shop_name FROM vendors WHERE id = ?',
        [collectedData.mantota_vendor_id]
      ) as any[];
      
      const adminNumber = process.env.ADMIN_WHATSAPP_NUMBER;
      if (adminNumber) {
        const { sendMessage } = await import('./whatsapp');
        await sendMessage(
          adminNumber,
          `🔔 NOUVEAU NUMÉRO PRO À CONFIGURER\nBoutique : ${vendorInfo[0]?.shop_name || 'N/A'}\nNuméro : ${message}\nID vendor : ${collectedData.mantota_vendor_id}\n→ Va sur Meta Business Manager pour l'ajouter`
        );
      }
      
      await updateConversation(phone, 'pro_awaiting_code', collectedData);
      return [
        `Parfait ! J'ai bien reçu ton numéro ${message} ✅\n\nNotre équipe va maintenant le connecter à ton bot. Tu vas recevoir un SMS avec un code de vérification sur ce numéro.\n\n⏳ Délai : 24-48h maximum\n\nDès que tu reçois le SMS, envoie-moi le code ici — même si je ne t'ai pas encore répondu !`
      ];

    case 'pro_awaiting_code':
      // Vérifier si le message ressemble à un code (4 à 8 chiffres uniquement)
      const codeRegex = /^\d{4,8}$/;
      if (codeRegex.test(message)) {
        // Stocker le code et mettre à jour le statut
        await pool.query(
          'UPDATE vendors SET meta_verification_code = ?, setup_status = "pending_code" WHERE id = ?',
          [message, collectedData.mantota_vendor_id]
        );
        
        // Alerter l'admin
        const [vendorInfo] = await pool.query(
          'SELECT shop_name FROM vendors WHERE id = ?',
          [collectedData.mantota_vendor_id]
        ) as any[];
        
        const adminNumber = process.env.ADMIN_WHATSAPP_NUMBER;
        if (adminNumber) {
          const { sendMessage } = await import('./whatsapp');
          await sendMessage(
            adminNumber,
            `🔑 CODE META REÇU\nBoutique : ${vendorInfo[0]?.shop_name || 'N/A'}\nCode : ${message}\n→ Valide maintenant sur Meta Business Manager\n→ Puis clique 'Activer' dans l'admin panel`
          );
        }
        
        await resetConversation(phone);
        return [
          "Code reçu ✅ Je transmets à notre équipe.\nTon bot sera actif dans quelques minutes.\nJe te confirme dès que c'est bon ! 🚀"
        ];
      } else {
        // Message n'est pas un code - utiliser GPT pour répondre naturellement
        const gptResponse = await callGPTMini(message);
        return [gptResponse + "\n\nN'oublie pas de m'envoyer le code SMS dès que tu le reçois 😊"];
      }

    case 'pro_setup_complete':
      // Ce step est déclenché depuis l'admin panel
      await processVendorSetupComplete(collectedData.mantota_vendor_id);
      await resetConversation(phone);
      return ["Configuration terminée !"];

    case 'vendor_customer_welcome':
      // Le client est dans une boutique active
      const vendorId = conversation.current_vendor_id;
      if (!vendorId) {
        // Plus de boutique active → retour accueil Tracy
        await resetConversation(phone);
        return [MESSAGES.welcome];
      }

      const activeVendor = await getVendorById(vendorId);
      if (!activeVendor) {
        await resetConversation(phone);
        return [MESSAGES.welcome];
      }

      // Charger les produits de cette boutique
      const products = await loadVendorProducts(vendorId);
      const productsList = products
        .filter((p: any) => p.statut === 'actif')
        .map((p: any) => `- ${p.nom} : ${p.prix} FCFA${p.description ? ' — ' + p.description : ''}`)
        .join('\n');

      // Construire le system prompt avec contexte boutique
      const systemPrompt = `Tu es l'assistant commercial de ${activeVendor.shop_name}.
Tu travailles pour cette boutique et tu réponds au nom de cette boutique uniquement.
Tu es chaleureux, professionnel, et tu pousses toujours vers l'achat de manière naturelle.

Boutique : ${activeVendor.shop_name}
Type : ${activeVendor.shop_type}
${activeVendor.shop_address ? 'Adresse : ' + activeVendor.shop_address : ''}
${activeVendor.localisation_lat ? 'Localisation GPS disponible' : ''}

Catalogue des produits disponibles :
${productsList || 'Catalogue en cours de mise à jour'}

Règles importantes :
- Réponds TOUJOURS en français informel et chaleureux
- Si le client demande un produit non disponible : propose le plus proche dans le catalogue
- Pousse toujours vers l'achat sans être agressif
- Si le client hésite : envoie un témoignage ou rappelle la qualité du produit
- Pour commander : demande son nom et adresse de livraison (si boutique physique)
- Ne parle JAMAIS d'autres boutiques
- Ne mentionne JAMAIS que tu es Tracy ou que tu es une IA — tu es l'assistant de ${activeVendor.shop_name} point final
- Si quelqu'un demande "tu es un robot ?" : réponds "Je suis l'assistant de ${activeVendor.shop_name}, là pour vous aider !"
- Si le client envoie un code boutique différent (ex: KF002) : ne réponds pas, le système changera de contexte automatiquement
- Quand le client exprime clairement une intention d'achat (ex: 'je veux commander', 'comment acheter', 'je prends [produit]', 'je veux ça'), réponds EXACTEMENT par ce JSON et rien d'autre : {"action":"start_order","product_mentioned":"[nom du produit mentionné ou null]"}`;

      const vendorGptResponse = await callGPTMini(message, systemPrompt);

      // Détection d'intention d'achat
      const trimmed = vendorGptResponse.trim();
      if (trimmed.startsWith('{"action":"start_order"')) {
        try {
          const parsed = JSON.parse(trimmed);
          await updateConversation(phone, 'order_collect_name', {
            current_vendor_id: vendorId,
            collected_data: {
              ...collectedData,
              ordering_product: parsed.product_mentioned
            }
          });
          return [
            "Super ! Je vais prendre ta commande 📦",
            "D'abord, c'est quoi ton nom complet ?"
          ];
        } catch {
          // Erreur parsing JSON, continuer avec réponse normale
        }
      }

      // Ajouter dans prospects_relance si pas encore présent pour ce vendor
      await addProspectIfNotExists(phone, vendorId, activeVendor.short_code);

      // Rester sur vendor_customer_welcome
      await updateConversation(phone, 'vendor_customer_welcome', {
        current_vendor_id: vendorId
      });

      return [vendorGptResponse];

    case 'order_collect_name':
      // Collecter le nom du client pour la commande
      collectedData.customer_name = message;
      await updateConversation(phone, 'order_collect_phone', {
        current_vendor_id: conversation.current_vendor_id,
        collected_data: collectedData
      });
      return [`Parfait ${message} ! Ton numéro de téléphone ?`];

    case 'order_collect_phone':
      // Valider et collecter le numéro de téléphone
      const orderPhoneRegex = /^\+[1-9]\d{7,14}$/;
      if (!orderPhoneRegex.test(message)) {
        return ["Ce numéro ne semble pas correct. Envoie-le avec l'indicatif (ex: +22901XXXXXXXX)"];
      }
      collectedData.customer_phone = message;
      await updateConversation(phone, 'order_collect_city', {
        current_vendor_id: conversation.current_vendor_id,
        collected_data: collectedData
      });
      return ["Tu es dans quelle ville ?"];

    case 'order_collect_city':
      // Collecter la ville et détecter le pays
      collectedData.city = message;
      collectedData.detected_country = detectCountry(collectedData.customer_phone);
      await updateConversation(phone, 'order_collect_address', {
        current_vendor_id: conversation.current_vendor_id,
        collected_data: collectedData
      });
      return [
        "Ok ! Donne-moi ton adresse ou un repère pour la livraison",
        "(quartier, rue, point de repère...)"
      ];

    case 'order_collect_address':
      // Collecter l'adresse et vérifier si produit déjà mentionné
      collectedData.landmark_indication = message;
      
      if (collectedData.ordering_product) {
        // Chercher le produit par nom
        const orderVendorId = conversation.current_vendor_id;
        if (orderVendorId) {
          const orderProducts = await loadVendorProducts(orderVendorId);
          const mentionedProduct = orderProducts.find((p: any) => 
            p.nom.toLowerCase().includes(collectedData.ordering_product.toLowerCase())
          );
          
          if (mentionedProduct) {
            collectedData.selected_product_id = mentionedProduct.id;
            collectedData.selected_product_name = mentionedProduct.nom;
            collectedData.selected_product_price = mentionedProduct.prix;
            await updateConversation(phone, 'order_confirm', {
              current_vendor_id: orderVendorId,
              collected_data: collectedData
            });
            return [
              `Parfait ! Tu commandes bien ${mentionedProduct.nom} à ${mentionedProduct.prix} FCFA ?`,
              "1️⃣ Oui, confirmer",
              "2️⃣ Non, choisir un autre produit"
            ];
          }
        }
      }
      
      // Passer à la sélection de produit
      await updateConversation(phone, 'order_select_product', {
        current_vendor_id: conversation.current_vendor_id,
        collected_data: collectedData
      });
      return ["Quel produit tu veux commander ? Je vais te montrer le catalogue..."];

    case 'order_select_product':
      // Afficher les produits disponibles
      const selectVendorId = conversation.current_vendor_id;
      if (!selectVendorId) {
        return ["Erreur : boutique non trouvée"];
      }
      const selectProducts = await loadVendorProducts(selectVendorId);
      
      const productList = selectProducts.map((p: any, i: number) => 
        `${i + 1}️⃣ ${p.nom} — ${p.prix} FCFA`
      ).join('\n');
      
      collectedData.available_products = selectProducts;
      await updateConversation(phone, 'order_awaiting_product_choice', {
        current_vendor_id: selectVendorId,
        collected_data: collectedData
      });
      return [
        "Quel produit tu veux commander ?\n\n" + productList
      ];

    case 'order_awaiting_product_choice':
      // Valider le choix de produit
      const choice = parseInt(message);
      const availableProducts = collectedData.available_products || [];
      
      if (isNaN(choice) || choice < 1 || choice > availableProducts.length) {
        return ["Choix invalide. Envoie le numéro du produit (1, 2, 3...)"];
      }
      
      const selectedProduct = availableProducts[choice - 1];
      collectedData.selected_product_id = selectedProduct.id;
      collectedData.selected_product_name = selectedProduct.nom;
      collectedData.selected_product_price = selectedProduct.prix;
      
      await updateConversation(phone, 'order_confirm', {
        current_vendor_id: conversation.current_vendor_id,
        collected_data: collectedData
      });
      return [
        `Tu commandes ${selectedProduct.nom} à ${selectedProduct.prix} FCFA.`,
        "Je confirme ?",
        "1️⃣ Oui, créer ma commande",
        "2️⃣ Non, annuler"
      ];

    case 'order_confirm':
      // Confirmation de la commande
      if (message === '2') {
        // Annuler
        await updateConversation(phone, 'vendor_customer_welcome', {
          current_vendor_id: conversation.current_vendor_id,
          collected_data: collectedData
        });
        return ["Commande annulée. Tu veux autre chose ?"];
      }
      
      if (message === '1') {
        // Créer la commande
        const confirmVendorId = conversation.current_vendor_id;
        if (!confirmVendorId) {
          return ["Erreur : boutique non trouvée"];
        }
        
        try {
          const orderResult = await mantotaService.createOrder({
            vendor_id: confirmVendorId,
            product_id: collectedData.selected_product_id,
            customer_name: collectedData.customer_name,
            customer_phone: collectedData.customer_phone,
            customer_whatsapp: phone,
            country: collectedData.detected_country,
            city: collectedData.city,
            landmark_indication: collectedData.landmark_indication
          });
          
          collectedData.order_reference = orderResult.reference;
          collectedData.order_id = orderResult.order_id;
          collectedData.tracking_token = orderResult.tracking_token;
          collectedData.tracking_url = orderResult.tracking_url;
          collectedData.delivery_pin = orderResult.delivery_pin;
          collectedData.amount = orderResult.amount;
          
          await updateConversation(phone, 'order_awaiting_payment', {
            current_vendor_id: confirmVendorId,
            collected_data: collectedData
          });
          
          // Notifier le vendeur en parallèle
          const vendor = await getVendorById(confirmVendorId);
          if (vendor) {
            const { sendMessage } = await import('./whatsapp');
            await sendMessage(
              vendor.phone_number,
              `🛍️ NOUVELLE COMMANDE EN ATTENTE\nRéférence : ${orderResult.reference}\nClient : ${collectedData.customer_name} — ${collectedData.customer_phone}\nProduit : ${collectedData.selected_product_name}\nVille : ${collectedData.city}\n\nEn attente du paiement client.\nJe te notifie dès que c'est payé ✅`
            );
          }
          
          return [
            `✅ Commande créée !\nRéférence : ${orderResult.reference}`,
            `💳 Paye maintenant via ce lien sécurisé :\n${orderResult.payment_url}\n\nMontant : ${orderResult.amount} FCFA\n(Mobile Money, carte bancaire...)`,
            `⏳ Dès que ton paiement est confirmé,\ntu recevras ton lien de suivi de commande.\nSi tu as déjà payé, écris-moi 'J'ai payé' 😊`
          ];
        } catch (error) {
          await logger.log('error', 'order', 'Erreur création commande', error);
          await updateConversation(phone, 'vendor_customer_welcome', {
            current_vendor_id: conversation.current_vendor_id,
            collected_data: collectedData
          });
          return [
            "Désolé, une erreur est survenue 😔",
            "Réessaie dans quelques instants ou contacte directement la boutique."
          ];
        }
      }
      
      return ["Choisis 1 pour confirmer ou 2 pour annuler"];

    case 'order_awaiting_payment':
      // Vérifier si le client a payé
      const lowerPaymentMessage = message.toLowerCase();
      if (lowerPaymentMessage.includes('payé') || lowerPaymentMessage.includes('paid') || lowerPaymentMessage.includes("j'ai payé")) {
        const orderStatus = await mantotaService.getOrderStatus(collectedData.order_reference);
        
        if (orderStatus && orderStatus.payment_status === 'paid') {
          await updateConversation(phone, 'order_paid_confirmed', {
            current_vendor_id: conversation.current_vendor_id,
            collected_data: collectedData
          });
          return ["🎉 Paiement confirmé ! Je t'envoie les détails de livraison..."];
        } else {
          return [
            "Hmm, je ne vois pas encore ton paiement 🔍",
            "Patiente quelques minutes et réessaie.",
            "Si le problème persiste, contacte-nous."
          ];
        }
      }
      
      // Réponse normale pour autres messages
      const paymentGptResponse = await callGPTMini(message);
      await updateConversation(phone, 'order_awaiting_payment', {
        current_vendor_id: conversation.current_vendor_id,
        collected_data: collectedData
      });
      return [paymentGptResponse + "\n\nSi tu as payé, écris-moi 'J'ai payé' 😊"];

    case 'order_paid_confirmed':
      // Paiement confirmé - envoyer détails de livraison
      const paidVendorId = conversation.current_vendor_id;
      if (!paidVendorId) {
        return ["Erreur : boutique non trouvée"];
      }
      
      const paidVendor = await getVendorById(paidVendorId);
      
      // Notifier le vendeur
      if (paidVendor) {
        const { sendMessage } = await import('./whatsapp');
        await sendMessage(
          paidVendor.phone_number,
          `💰 PAIEMENT CONFIRMÉ !\nCommande : ${collectedData.order_reference}\nClient : ${collectedData.customer_name} — ${collectedData.customer_phone}\nAdresse : ${collectedData.city} — ${collectedData.landmark_indication}\nProduit : ${collectedData.selected_product_name}\nMontant : ${collectedData.amount} FCFA (en escrow)\n\n➡️ Va sur mantota.com pour confirmer la livraison et désigner un livreur.\nL'argent sera libéré après livraison confirmée.`
        );
      }
      
      // Retourner au mode normal de la boutique
      await updateConversation(phone, 'vendor_customer_welcome', {
        current_vendor_id: paidVendorId,
        collected_data: collectedData
      });
      
      return [
        `🎉 Paiement confirmé ! Merci ${collectedData.customer_name} !\nRéférence : ${collectedData.order_reference}\nLien de suivi de ta commande :\n${collectedData.tracking_url}\n\nGarde ce lien — tu pourras suivre ta livraison et confirmer la réception.`,
        `🔐 Ton code de livraison : ${collectedData.delivery_pin}\nCommunique ce code au livreur UNIQUEMENT quand tu as bien reçu ta commande.\n(Ne le donne jamais avant !)`
      ];

    default:
      const gptResponse = await callGPTMini(message);
      await updateConversation(phone, currentStep, collectedData);
      return [gptResponse];
  }
}

// Fonction exportée pour finaliser la configuration Pro (appelée depuis l'admin)
export async function processVendorSetupComplete(vendor_id: number): Promise<void> {
  try {
    // Charger le vendor depuis DB
    const [vendorRows] = await pool.query(
      'SELECT * FROM vendors WHERE id = ?',
      [vendor_id]
    ) as any[];

    if (vendorRows.length === 0) {
      await logger.log('error', 'setup', `Vendor introuvable pour activation: ${vendor_id}`);
      return;
    }

    const vendor = vendorRows[0];

    // Mettre setup_status à 'active' et bot_status à 'active'
    await pool.query(
      'UPDATE vendors SET setup_status = "active", bot_status = "active" WHERE id = ?',
      [vendor_id]
    );

    // Logger l'activation
    await logger.log('info', 'setup', `Setup Pro terminé pour vendor ${vendor_id} - numéro ${vendor.whatsapp_number}`);

    // Envoyer le message de confirmation au client
    const { sendMessage } = await import('./whatsapp');
    await sendMessage(
      vendor.phone_number,
      `🎉 Ton numéro est configuré et actif !\n\nTes clients peuvent maintenant t'écrire directement au ${vendor.whatsapp_number} et je gérerai toutes les conversations en ton nom.\n\nPour tester :\n📱 Envoie 'Bonjour' depuis un autre téléphone à ton numéro ${vendor.whatsapp_number}\n\nBonne vente ! 🚀`
    );
  } catch (error) {
    await logger.log('error', 'setup', `Erreur activation setup Pro pour vendor ${vendor_id}`, error);
  }
}

// Fonction pour notifier un vendeur d'un litige
export async function notifyVendorDispute(
  vendorPhone: string,
  orderRef: string,
  customerName: string,
  disputeReason: string
): Promise<void> {
  try {
    const { sendMessage } = await import('./whatsapp');
    await sendMessage(
      vendorPhone,
      `🔴 LITIGE OUVERT\nCommande : ${orderRef}\nClient : ${customerName}\nMotif : ${disputeReason}\n\nL'argent reste bloqué jusqu'à résolution.\n➡️ Réponds depuis mantota.com/orders`
    );
    await logger.log('info', 'dispute', `Notification litige envoyée à ${vendorPhone} pour commande ${orderRef}`);
  } catch (error) {
    await logger.log('error', 'dispute', 'Erreur notification litige', error);
    throw error;
  }
}
