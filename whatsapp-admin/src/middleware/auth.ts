import { Request, Response, NextFunction } from 'express';
import jwt from 'jsonwebtoken';

const JWT_SECRET = process.env.SESSION_SECRET || 'change-me-in-production';

export function requireAuth(req: Request, res: Response, next: NextFunction): void {
  // Exclure les routes d'authentification
  if (req.path === '/login' || req.path === '/logout') {
    return next();
  }
  
  const token = req.cookies?.auth_token;
  
  if (!token) {
    return res.redirect('/login');
  }
  
  try {
    const decoded = jwt.verify(token, JWT_SECRET) as { adminId: number };
    (req as any).adminId = decoded.adminId;
    next();
  } catch (error) {
    res.clearCookie('auth_token');
    res.redirect('/login');
  }
}

export function noindex(req: Request, res: Response, next: NextFunction): void {
  res.setHeader('X-Robots-Tag', 'noindex, nofollow');
  next();
}
