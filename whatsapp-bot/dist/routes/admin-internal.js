"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const conversation_1 = require("../services/conversation");
const connection_1 = __importDefault(require("../db/connection"));
const router = (0, express_1.Router)();
// Route interne pour l'admin panel - finaliser la configuration Pro
router.post('/setup-complete', async (req, res) => {
    try {
        const apiKey = req.headers['x-bot-api-key'];
        const expectedApiKey = process.env.BOT_API_KEY;
        if (!expectedApiKey || apiKey !== expectedApiKey) {
            return res.status(403).json({ success: false, error: 'API key invalide' });
        }
        const { vendor_id } = req.body;
        if (!vendor_id) {
            return res.status(400).json({ success: false, error: 'vendor_id requis' });
        }
        await (0, conversation_1.processVendorSetupComplete)(vendor_id);
        res.json({ success: true });
    }
    catch (error) {
        console.error('Erreur setup-complete:', error);
        res.status(500).json({ success: false, error: 'Erreur serveur' });
    }
});
// Route interne pour notifier un vendeur d'un litige (appelée depuis Laravel Mantota)
router.post('/dispute-notify', async (req, res) => {
    try {
        const apiKey = req.headers['x-bot-api-key'];
        const expectedApiKey = process.env.BOT_API_KEY;
        if (!expectedApiKey || apiKey !== expectedApiKey) {
            return res.status(403).json({ success: false, error: 'API key invalide' });
        }
        const { vendor_id, order_reference, customer_name, dispute_reason } = req.body;
        if (!vendor_id || !order_reference || !customer_name || !dispute_reason) {
            return res.status(400).json({ success: false, error: 'Champs manquants' });
        }
        // Récupérer le numéro de téléphone du vendeur
        const [vendorRows] = await connection_1.default.query('SELECT phone_number FROM vendors WHERE id = ?', [vendor_id]);
        if (vendorRows.length === 0) {
            return res.status(404).json({ success: false, error: 'Vendeur non trouvé' });
        }
        const vendorPhone = vendorRows[0].phone_number;
        // Notifier le vendeur
        await (0, conversation_1.notifyVendorDispute)(vendorPhone, order_reference, customer_name, dispute_reason);
        res.json({ success: true });
    }
    catch (error) {
        console.error('Erreur dispute-notify:', error);
        res.status(500).json({ success: false, error: 'Erreur serveur' });
    }
});
exports.default = router;
