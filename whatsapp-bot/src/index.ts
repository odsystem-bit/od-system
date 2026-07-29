import express from 'express';
import dotenv from 'dotenv';
import webhookRoutes from './routes/webhook';
import adminInternalRoutes from './routes/admin-internal';
import { startCronJobs } from './jobs/cron';

dotenv.config();

const app = express();
const port = process.env.PORT || 3000;

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use('/', webhookRoutes);
app.use('/api/admin', adminInternalRoutes);

app.listen(port, () => {
  console.log(`Serveur WhatsApp démarré sur le port ${port}`);
  startCronJobs();
});
