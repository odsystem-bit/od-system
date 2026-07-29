import { Router, Request, Response } from 'express';
import pool from '../db/connection';

const router = Router();

router.get('/', async (req: Request, res: Response) => {
  try {
    // Card 1 : Clients actifs
    const [activeRows] = await pool.query(
      'SELECT COUNT(*) as count FROM vendors WHERE bot_status = "active"'
    ) as any[];
    const activeCount = activeRows[0].count;

    // Card 2 : Clients suspendus
    const [suspendedRows] = await pool.query(
      'SELECT COUNT(*) as count FROM vendors WHERE bot_status = "suspended"'
    ) as any[];
    const suspendedCount = suspendedRows[0].count;

    // Card 3 : Revenus du mois
    const [revenueRows] = await pool.query(
      'SELECT SUM(pc.prix) as total FROM subscriptions s JOIN plan_config pc ON s.plan = pc.plan WHERE MONTH(s.created_at) = MONTH(NOW()) AND s.status = "active" AND s.plan != "gratuit"'
    ) as any[];
    const monthlyRevenue = revenueRows[0].total || 0;

    // Card 4 : Conversations aujourd'hui
    const [convRows] = await pool.query(
      'SELECT SUM(conversations_count) as total FROM subscriptions WHERE DATE(created_at) = CURDATE()'
    ) as any[];
    const todayConversations = convRows[0].total || 0;

    // Erreurs récentes (24h)
    const [errorRows] = await pool.query(
      'SELECT * FROM logs WHERE level = "error" AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 5'
    ) as any[];
    const recentErrors = errorRows.map((row: any) => ({
      time: new Date(row.created_at).toLocaleTimeString('fr-FR'),
      service: row.service,
      message: row.message
    }));

    // Cron jobs derniers passages
    const [cronRows] = await pool.query(
      'SELECT message, created_at FROM logs WHERE service = "cron" ORDER BY created_at DESC LIMIT 10'
    ) as any[];
    const cronLogs = cronRows.map((row: any) => ({
      task: row.message,
      lastRun: new Date(row.created_at).toLocaleString('fr-FR'),
      status: 'OK'
    }));

    // Nouveaux clients par jour cette semaine
    const [newClientsRows] = await pool.query(
      'SELECT DATE(created_at) as date, COUNT(*) as count FROM vendors WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY date'
    ) as any[];
    
    const newClientsByDay = newClientsRows.map((row: any) => ({
      date: new Date(row.date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }),
      count: row.count
    }));

    res.render('dashboard', {
      activeCount,
      suspendedCount,
      monthlyRevenue,
      todayConversations,
      recentErrors,
      cronLogs,
      newClientsByDay
    });
  } catch (error) {
    console.error('Erreur dashboard:', error);
    res.status(500).send('Erreur serveur');
  }
});

export default router;
