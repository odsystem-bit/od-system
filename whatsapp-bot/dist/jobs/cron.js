"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.startCronJobs = startCronJobs;
const node_cron_1 = __importDefault(require("node-cron"));
const connection_1 = __importDefault(require("../db/connection"));
const whatsapp_1 = require("../services/whatsapp");
const billingService = __importStar(require("../services/billing"));
const scannerService = __importStar(require("../services/scanner"));
const logger = __importStar(require("../services/logger"));
function startCronJobs() {
    // TÂCHE 1 — Vérification expirations (chaque jour à minuit)
    node_cron_1.default.schedule('0 0 * * *', async () => {
        await logger.log('info', 'cron', 'Tâche check_expirations démarrée');
        try {
            const [rows] = await connection_1.default.query('SELECT s.*, v.phone_number, v.name FROM subscriptions s JOIN vendors v ON s.vendor_id = v.id WHERE s.date_fin < CURDATE() AND s.status = "active"', []);
            for (const subscription of rows) {
                await connection_1.default.query('UPDATE subscriptions SET status = "expired" WHERE id = ?', [subscription.id]);
                await connection_1.default.query('UPDATE vendors SET bot_status = "suspended" WHERE id = ?', [subscription.vendor_id]);
                const message = await billingService.getRenewalMessage(subscription.vendor_id, 0);
                await (0, whatsapp_1.sendMessage)(subscription.phone_number, message);
            }
            await logger.log('info', 'cron', `${rows.length} abonnements expirés traités`);
        }
        catch (error) {
            await logger.log('error', 'cron', 'Erreur check_expirations', error);
        }
    });
    // TÂCHE 2 — Rappels renouvellement J-3 (chaque jour à 9h)
    node_cron_1.default.schedule('0 9 * * *', async () => {
        await logger.log('info', 'cron', 'Tâche renewal_reminders démarrée');
        try {
            const [rows] = await connection_1.default.query('SELECT s.*, v.phone_number FROM subscriptions s JOIN vendors v ON s.vendor_id = v.id WHERE s.date_fin = CURDATE() + INTERVAL 3 DAY AND s.status = "active"', []);
            for (const subscription of rows) {
                const message = await billingService.getRenewalMessage(subscription.vendor_id, 3);
                await (0, whatsapp_1.sendMessage)(subscription.phone_number, message);
            }
            await logger.log('info', 'cron', `${rows.length} rappels renouvellement envoyés`);
        }
        catch (error) {
            await logger.log('error', 'cron', 'Erreur renewal_reminders', error);
        }
    });
    // TÂCHE 3 — Relance prospects (chaque heure)
    node_cron_1.default.schedule('0 * * * *', async () => {
        await logger.log('info', 'cron', 'Tâche prospect_relance démarrée');
        try {
            const [rows] = await connection_1.default.query('SELECT pr.*, v.phone_number, v.shop_name FROM prospects_relance pr JOIN vendors v ON pr.vendor_id = v.id WHERE pr.relance_sent = 0 AND pr.last_interaction_at < NOW() - INTERVAL 24 HOUR', []);
            for (const prospect of rows) {
                const message = `Salut 👋 Tu t'es renseigné sur ${prospect.shop_name} hier.\nTu as des questions ? Je suis là pour t'aider 😊`;
                await (0, whatsapp_1.sendMessage)(prospect.phone_number, message);
                await connection_1.default.query('UPDATE prospects_relance SET relance_sent = 1, relance_at = NOW() WHERE id = ?', [prospect.id]);
            }
            await logger.log('info', 'cron', `${rows.length} relances prospects envoyées`);
        }
        catch (error) {
            await logger.log('error', 'cron', 'Erreur prospect_relance', error);
        }
    });
    // TÂCHE 4 — Calcul surplus en fin de mois (1er de chaque mois à 1h)
    node_cron_1.default.schedule('0 1 1 * *', async () => {
        await logger.log('info', 'cron', 'Tâche surplus_calculation démarrée');
        try {
            const [rows] = await connection_1.default.query('SELECT s.*, v.phone_number FROM subscriptions s JOIN vendors v ON s.vendor_id = v.id WHERE s.surplus_count > 0 AND s.status = "active" AND s.plan != "gratuit" AND s.surplus_link_sent = 0', []);
            for (const subscription of rows) {
                const paymentLink = await billingService.generatePaymentLink(subscription.vendor_id, 'surplus', subscription.surplus_amount);
                const message = `Tu as un surplus de ${subscription.surplus_count} conversations ce mois.\nMontant dû : ${subscription.surplus_amount} FCFA\n\nPaiement surplus : ${paymentLink}`;
                await (0, whatsapp_1.sendMessage)(subscription.phone_number, message);
                await connection_1.default.query('UPDATE subscriptions SET surplus_link_sent = 1 WHERE id = ?', [subscription.id]);
            }
            await logger.log('info', 'cron', `${rows.length} factures surplus envoyées`);
        }
        catch (error) {
            await logger.log('error', 'cron', 'Erreur surplus_calculation', error);
        }
    });
    // TÂCHE 5 — Scan hebdomadaire des sites externes (chaque lundi à 6h)
    node_cron_1.default.schedule('0 6 * * 1', async () => {
        await logger.log('info', 'cron', 'Tâche weekly_site_scan démarrée');
        try {
            const [rows] = await connection_1.default.query('SELECT * FROM vendors WHERE site_externe IS NOT NULL AND bot_status = "active"', []);
            for (const vendor of rows) {
                try {
                    // Charger les produits actuels depuis la table products
                    const [productRows] = await connection_1.default.query('SELECT nom, prix, description, categorie FROM products WHERE vendor_id = ? AND statut = "actif"', [vendor.id]);
                    const currentProducts = productRows.map((p) => ({
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
                        await connection_1.default.query('INSERT INTO vendors_pending (phone_number, name, shop_type, scanned_products_json, status) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE scanned_products_json = ?, status = ?', [vendor.phone_number, vendor.name, vendor.shop_type, JSON.stringify(newProducts), 'ready', JSON.stringify(newProducts), 'ready']);
                        // Formater et envoyer le message
                        const message = scannerService.formatChangesMessage(compareResult);
                        await (0, whatsapp_1.sendMessage)(vendor.phone_number, message);
                        // Stocker dans conversations que le vendeur est en attente
                        await connection_1.default.query('INSERT INTO conversations (phone_number, current_step, collected_data, client_type, vendor_id) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE current_step = ?, collected_data = ?, vendor_id = ?', [vendor.phone_number, 'confirm_site_update', JSON.stringify({}), 'vendor_management', vendor.id, 'confirm_site_update', JSON.stringify({}), vendor.id]);
                        await logger.log('info', 'cron', `Changements détectés pour ${vendor.name}, message envoyé`);
                    }
                }
                catch (error) {
                    await logger.log('error', 'cron', `Erreur scan site pour ${vendor.name}`, error);
                }
            }
            await logger.log('info', 'cron', `${rows.length} sites scannés`);
        }
        catch (error) {
            await logger.log('error', 'cron', 'Erreur weekly_site_scan', error);
        }
    });
    // TÂCHE 6 — Vérification plan gratuit non renouvelable (chaque jour à 2h)
    node_cron_1.default.schedule('0 2 * * *', async () => {
        await logger.log('info', 'cron', 'Tâche check_gratuit_expired démarrée');
        try {
            const [rows] = await connection_1.default.query('SELECT s.*, v.phone_number FROM subscriptions s JOIN vendors v ON s.vendor_id = v.id WHERE s.plan = "gratuit" AND s.date_fin < CURDATE() AND s.status = "active"', []);
            for (const subscription of rows) {
                await connection_1.default.query('UPDATE subscriptions SET status = "expired" WHERE id = ?', [subscription.id]);
                await connection_1.default.query('UPDATE vendors SET bot_status = "inactive" WHERE id = ?', [subscription.vendor_id]);
                const starterLink = await billingService.generatePaymentLink(subscription.vendor_id, 'starter');
                const standardLink = await billingService.generatePaymentLink(subscription.vendor_id, 'standard');
                const proLink = await billingService.generatePaymentLink(subscription.vendor_id, 'pro');
                const message = `Ton mois gratuit est terminé 🙏
Pour continuer à recevoir des clients, choisis un plan :

⭐ Starter — 2 500 FCFA/mois
${starterLink}

🚀 Standard — 6 000 FCFA/mois
${standardLink}

💎 Pro — 12 000 FCFA/mois
${proLink}

Le plan gratuit est disponible une seule fois par numéro WhatsApp.`;
                await (0, whatsapp_1.sendMessage)(subscription.phone_number, message);
            }
            await logger.log('info', 'cron', `${rows.length} plans gratuits expirés traités`);
        }
        catch (error) {
            await logger.log('error', 'cron', 'Erreur check_gratuit_expired', error);
        }
        // Nettoyer les anciens logs
        await logger.clearOldLogs();
    });
    console.log('🚀 Tous les cron jobs sont démarrés');
}
