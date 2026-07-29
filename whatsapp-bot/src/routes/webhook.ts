import { Router, Request, Response } from 'express';
import { processMessage } from '../services/conversation';
import { sendMessage } from '../services/whatsapp';
import * as logger from '../services/logger';

const router = Router();

router.get('/webhook', (req: Request, res: Response) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  if (mode === 'subscribe' && token === process.env.WHATSAPP_VERIFY_TOKEN) {
    res.status(200).send(challenge);
  } else {
    res.sendStatus(403);
  }
});

router.post('/webhook', async (req: Request, res: Response) => {
  const body = req.body;

  if (body.object === 'whatsapp_business_account') {
    res.sendStatus(200);

    const entry = body.entry?.[0];
    const changes = entry?.changes?.[0];
    const value = changes?.value;

    if (value?.messages) {
      const message = value.messages[0];
      const from = message.from;
      const text = message.text?.body;
      const messageType = message.type;
      let imageId: string | null = null;

      if (messageType === 'image' && message.image?.id) {
        imageId = message.image.id;
      }

      // Masquer le numéro pour la confidentialité
      const maskedPhone = from.substring(0, 4) + '****' + from.substring(from.length - 2);
      await logger.log('info', 'webhook', `Message de ${maskedPhone} (${messageType})`);

      try {
        const responses = await processMessage(from, text || '', messageType, imageId);
        
        for (const response of responses) {
          await sendMessage(from, response);
        }
      } catch (error) {
        await logger.log('error', 'webhook', `Erreur traitement message: ${maskedPhone}`, error);
      }
    }
  } else {
    res.sendStatus(200);
  }
});

export default router;
