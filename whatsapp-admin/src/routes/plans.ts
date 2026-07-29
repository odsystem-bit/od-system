import { Router, Request, Response } from 'express';
import pool from '../db/connection';

const router = Router();

router.get('/', async (req: Request, res: Response) => {
  try {
    const [rows] = await pool.query('SELECT * FROM plan_config ORDER BY plan') as any[];
    res.render('plans', { plans: rows });
  } catch (error) {
    console.error('Erreur plans:', error);
    res.status(500).send('Erreur serveur');
  }
});

router.post('/update', async (req: Request, res: Response) => {
  try {
    const { gratuit_prix, gratuit_conversations, gratuit_surplus,
            starter_prix, starter_conversations, starter_surplus,
            standard_prix, standard_conversations, standard_surplus,
            pro_prix, pro_conversations, pro_surplus } = req.body;

    await pool.query(
      'UPDATE plan_config SET prix = ?, conversations = ?, surplus_per_100 = ? WHERE plan = "gratuit"',
      [gratuit_prix, gratuit_conversations, gratuit_surplus]
    );

    await pool.query(
      'UPDATE plan_config SET prix = ?, conversations = ?, surplus_per_100 = ? WHERE plan = "starter"',
      [starter_prix, starter_conversations, starter_surplus]
    );

    await pool.query(
      'UPDATE plan_config SET prix = ?, conversations = ?, surplus_per_100 = ? WHERE plan = "standard"',
      [standard_prix, standard_conversations, standard_surplus]
    );

    await pool.query(
      'UPDATE plan_config SET prix = ?, conversations = ?, surplus_per_100 = ? WHERE plan = "pro"',
      [pro_prix, pro_conversations, pro_surplus]
    );

    req.session.flash = { type: 'success', msg: '✅ Plans mis à jour avec succès' };
    res.redirect('/plans');
  } catch (error) {
    console.error('Erreur update plans:', error);
    res.status(500).send('Erreur serveur');
  }
});

export default router;
