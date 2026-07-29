import express from 'express';
import session from 'express-session';
import MySQLStoreFactory from 'express-mysql-session';
import dotenv from 'dotenv';
import path from 'path';
import { requireAuth, noindex } from './middleware/auth';
import authRoutes from './routes/auth';
import dashboardRoutes from './routes/dashboard';
import clientsRoutes from './routes/clients';
import logsRoutes from './routes/logs';
import plansRoutes from './routes/plans';

// Extension du type Session pour inclure flash
declare module 'express-session' {
  interface SessionData {
    adminId?: number;
    flash?: { type: string; msg: string };
  }
}

dotenv.config();

const app = express();
const port = process.env.ADMIN_PORT || 4000;

const MySQLStore = MySQLStoreFactory(session);
const sessionStore = new MySQLStore({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'whatsapp_bot',
});

app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.set('views', path.join(__dirname, '../src/views'));
app.set('view engine', 'ejs');

app.use(session({
  secret: process.env.SESSION_SECRET || 'change-me-in-production',
  store: sessionStore,
  resave: false,
  saveUninitialized: false,
  cookie: {
    secure: process.env.NODE_ENV === 'production',
    httpOnly: true,
    maxAge: 8 * 60 * 60 * 1000, // 8 heures
  },
}));

app.use(noindex);

// Routes d'authentification (login/logout) - sans protection
app.use('/', authRoutes);

// Middleware d'authentification pour protéger les routes suivantes
app.use(requireAuth);

// Routes protégées
app.use('/', dashboardRoutes);
app.use('/clients', clientsRoutes);
app.use('/logs', logsRoutes);
app.use('/plans', plansRoutes);

app.listen(port, () => {
  console.log(`Dashboard admin démarré sur le port ${port}`);
});
