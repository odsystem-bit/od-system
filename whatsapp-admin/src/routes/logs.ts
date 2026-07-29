import { Router, Request, Response } from 'express';
import pool from '../db/connection';

const router = Router();

router.get('/', async (req: Request, res: Response) => {
  try {
    const { level, service } = req.query;
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

    query += ' ORDER BY created_at DESC LIMIT 200';

    const [rows] = await pool.query(query, params) as any[];

    res.render('logs', { logs: rows, filters: { level, service } });
  } catch (error) {
    console.error('Erreur logs:', error);
    res.status(500).send('Erreur serveur');
  }
});

router.get('/api', async (req: Request, res: Response) => {
  try {
    const { level, service, limit = 10 } = req.query;
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
    params.push(parseInt(limit as string));

    const [rows] = await pool.query(query, params) as any[];

    res.json(rows);
  } catch (error) {
    console.error('Erreur logs api:', error);
    res.status(500).json({ error: 'Erreur serveur' });
  }
});

router.post('/clear-old', async (req: Request, res: Response) => {
  try {
    const [result] = await pool.query('DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)') as any[];
    const deletedCount = result.affectedRows;
    req.session.flash = { type: 'success', msg: `✅ ${deletedCount} logs supprimés` };
    res.redirect('/logs');
  } catch (error) {
    console.error('Erreur clear old logs:', error);
    res.status(500).send('Erreur serveur');
  }
});

export default router;
