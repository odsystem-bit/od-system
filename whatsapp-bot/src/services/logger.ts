import pool from '../db/connection';

const ADMIN_PHONE = process.env.ADMIN_WHATSAPP_NUMBER;

export interface Log {
  id: number;
  level: 'info' | 'warning' | 'error';
  service: string;
  message: string;
  details: any;
  created_at: Date;
}

export async function log(
  level: 'info' | 'warning' | 'error',
  service: string,
  message: string,
  details?: any
): Promise<void> {
  try {
    // Écrire dans la table logs MySQL
    const detailsJson = details ? JSON.stringify(details) : null;
    await pool.query(
      'INSERT INTO logs (level, service, message, details) VALUES (?, ?, ?, ?)',
      [level, service, message, detailsJson]
    );

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
  } catch (error) {
    // Le logger ne doit JAMAIS faire planter le serveur
    console.error('Erreur logger (silencieuse):', error);
  }
}

export async function getLogs(
  level?: string,
  service?: string,
  limit: number = 100
): Promise<Log[]> {
  try {
    let query = 'SELECT * FROM logs WHERE 1=1';
    const params: any[] = [];

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

    const [rows] = await pool.query(query, params) as any[];
    return rows.map((row: any) => ({
      ...row,
      details: row.details ? JSON.parse(row.details) : null,
    }));
  } catch (error) {
    console.error('Erreur getLogs:', error);
    return [];
  }
}

export async function getErrorCount(hours: number = 24): Promise<number> {
  try {
    const [rows] = await pool.query(
      'SELECT COUNT(*) as count FROM logs WHERE level = "error" AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)',
      [hours]
    ) as any[];
    return rows[0]?.count || 0;
  } catch (error) {
    console.error('Erreur getErrorCount:', error);
    return 0;
  }
}

export async function clearOldLogs(): Promise<void> {
  try {
    await pool.query(
      'DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );
    await log('info', 'cron', 'Anciens logs supprimés');
  } catch (error) {
    console.error('Erreur clearOldLogs:', error);
  }
}

export async function getPlanConfig(): Promise<Record<string, any>> {
  try {
    const [rows] = await pool.query('SELECT * FROM plan_config') as any[];
    const plans: Record<string, any> = {};
    for (const row of rows) {
      plans[row.plan] = row;
    }
    return plans;
  } catch (error) {
    console.error('Erreur getPlanConfig:', error);
    return {};
  }
}
