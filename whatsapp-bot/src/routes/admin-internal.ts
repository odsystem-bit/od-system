import { Router, Request, Response } from 'express';
import { processVendorSetupComplete, notifyVendorDispute } from '../services/conversation';
import pool from '../db/connection';

const router = Router();

// Route interne pour l'admin panel - finaliser la configuration Pro
router.post('/setup-complete', async (req: Request, res: Response) => {
  try {
    const apiKey = req.headers['x-bot-api-key'] as string;
    const expectedApiKey = process.env.BOT_API_KEY;

    if (!expectedApiKey || apiKey !== expectedApiKey) {
      return res.status(403).json({ success: false, error: 'API key invalide' });
    }

    const { vendor_id } = req.body;

    if (!vendor_id) {
      return res.status(400).json({ success: false, error: 'vendor_id requis' });
    }

    await processVendorSetupComplete(vendor_id);

    res.json({ success: true });
  } catch (error) {
    console.error('Erreur setup-complete:', error);
    res.status(500).json({ success: false, error: 'Erreur serveur' });
  }
});

// Route interne pour notifier un vendeur d'un litige (appelée depuis Laravel Mantota)
router.post('/dispute-notify', async (req: Request, res: Response) => {
  try {
    const apiKey = req.headers['x-bot-api-key'] as string;
    const expectedApiKey = process.env.BOT_API_KEY;

    if (!expectedApiKey || apiKey !== expectedApiKey) {
      return res.status(403).json({ success: false, error: 'API key invalide' });
    }

    const { vendor_id, order_reference, customer_name, dispute_reason } = req.body;

    if (!vendor_id || !order_reference || !customer_name || !dispute_reason) {
      return res.status(400).json({ success: false, error: 'Champs manquants' });
    }

    // Récupérer le numéro de téléphone du vendeur
    const [vendorRows] = await pool.query(
      'SELECT phone_number FROM vendors WHERE id = ?',
      [vendor_id]
    ) as any[];

    if (vendorRows.length === 0) {
      return res.status(404).json({ success: false, error: 'Vendeur non trouvé' });
    }

    const vendorPhone = vendorRows[0].phone_number;

    // Notifier le vendeur
    await notifyVendorDispute(vendorPhone, order_reference, customer_name, dispute_reason);

    res.json({ success: true });
  } catch (error) {
    console.error('Erreur dispute-notify:', error);
    res.status(500).json({ success: false, error: 'Erreur serveur' });
  }
});

export default router;
