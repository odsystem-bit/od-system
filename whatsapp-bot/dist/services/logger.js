"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.log = log;
exports.getLogs = getLogs;
exports.getErrorCount = getErrorCount;
exports.clearOldLogs = clearOldLogs;
exports.getPlanConfig = getPlanConfig;
const connection_1 = __importDefault(require("../db/connection"));
const ADMIN_PHONE = process.env.ADMIN_WHATSAPP_NUMBER;
async function log(level, service, message, details) {
    try {
        // Écrire dans la table logs MySQL
        const detailsJson = details ? JSON.stringify(details) : null;
        await connection_1.default.query('INSERT INTO logs (level, service, message, details) VALUES (?, ?, ?, ?)', [level, service, message, detailsJson]);
        // Si niveau error et ADMIN_PHONE défini, envoyer notification WhatsApp
        if (level === 'error' && ADMIN_PHONE) {
            const detailsStr = details ? JSON.stringify(details).substring(0, 200) : 'N/A';
            const now = new Date().toLocaleString('fr-FR', { timeZone: 'Africa/Porto-Novo' });
            const alertMessage = `🔴 ERREUR TRACY
Service : ${service}
Message : ${message}
Heure : ${now}
Détails : ${detailsStr}`;
            // Import lazy pour éviter les imports circulaires
            const { sendMessage } = require('./whatsapp');
            await sendMessage(ADMIN_PHONE, alertMessage);
        }
    }
    catch (error) {
        // Le logger ne doit JAMAIS faire planter le serveur
        console.error('Erreur logger (silencieuse):', error);
    }
}
async function getLogs(level, service, limit = 100) {
    try {
        let query = 'SELECT * FROM logs WHERE 1=1';
        const params = [];
        if (level) {
            query += ' AND level = ?';
            params.push(level);
        }
        if (service) {
            query += ' AND service = ?';
            params.push(service);
        }
        query += ' ORDER BY created_at DESC LIMIT ?';
        params.push(limit);
        const [rows] = await connection_1.default.query(query, params);
        return rows.map((row) => ({
            ...row,
            details: row.details ? JSON.parse(row.details) : null,
        }));
    }
    catch (error) {
        console.error('Erreur getLogs:', error);
        return [];
    }
}
async function getErrorCount(hours = 24) {
    try {
        const [rows] = await connection_1.default.query('SELECT COUNT(*) as count FROM logs WHERE level = "error" AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)', [hours]);
        return rows[0]?.count || 0;
    }
    catch (error) {
        console.error('Erreur getErrorCount:', error);
        return 0;
    }
}
async function clearOldLogs() {
    try {
        await connection_1.default.query('DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
        await log('info', 'cron', 'Anciens logs supprimés');
    }
    catch (error) {
        console.error('Erreur clearOldLogs:', error);
    }
}
async function getPlanConfig() {
    try {
        const [rows] = await connection_1.default.query('SELECT * FROM plan_config');
        const plans = {};
        for (const row of rows) {
            plans[row.plan] = row;
        }
        return plans;
    }
    catch (error) {
        console.error('Erreur getPlanConfig:', error);
        return {};
    }
}
