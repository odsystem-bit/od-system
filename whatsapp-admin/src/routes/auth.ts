import { Router, Request, Response } from 'express';
import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import pool from '../db/connection';

const router = Router();
const JWT_SECRET = process.env.SESSION_SECRET || 'change-me-in-production';

// Rate limiting simple en mémoire par IP
const loginAttempts = new Map<string, { count: number; resetTime: number }>();

function checkRateLimit(ip: string): boolean {
  const now = Date.now();
  const attempts = loginAttempts.get(ip);

  if (!attempts || now > attempts.resetTime) {
    loginAttempts.set(ip, { count: 1, resetTime: now + 10 * 60 * 1000 });
    return true;
  }

  if (attempts.count >= 5) {
    return false;
  }

  attempts.count++;
  return true;
}

router.get('/login', (req: Request, res: Response) => {
  res.render('login', { error: null });
});

router.post('/login', async (req: Request, res: Response) => {
  const { email, password } = req.body;
  const ip = req.ip || req.connection.remoteAddress || 'unknown';

  if (!checkRateLimit(ip)) {
    return res.render('login', { error: 'Trop de tentatives. Réessaie dans 10 minutes.' });
  }

  try {
    const [rows] = await pool.query(
      'SELECT * FROM admin_users WHERE email = ?',
      [email]
    ) as any[];

    if (rows.length === 0) {
      return res.render('login', { error: 'Identifiants incorrects' });
    }

    const admin = rows[0];
    const isValid = await bcrypt.compare(password, admin.password_hash);

    if (!isValid) {
      return res.render('login', { error: 'Identifiants incorrects' });
    }

    await pool.query('UPDATE admin_users SET last_login = NOW() WHERE id = ?', [admin.id]);
    
    // Générer JWT token
    const token = jwt.sign({ adminId: admin.id }, JWT_SECRET, { expiresIn: '8h' });
    
    // Renvoyer le token dans un cookie httpOnly
    res.cookie('auth_token', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      maxAge: 8 * 60 * 60 * 1000,
      sameSite: 'lax'
    });
    
    res.redirect('/');
  } catch (error) {
    console.error('Erreur login:', error);
    res.render('login', { error: 'Erreur serveur' });
  }
});

router.get('/logout', (req: Request, res: Response) => {
  res.clearCookie('auth_token');
  res.redirect('/login');
});

export default router;
