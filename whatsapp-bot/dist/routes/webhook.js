"use strict";
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    var desc = Object.getOwnPropertyDescriptor(m, k);
    if (!desc || ("get" in desc ? !m.__esModule : desc.writable || desc.configurable)) {
      desc = { enumerable: true, get: function() { return m[k]; } };
    }
    Object.defineProperty(o, k2, desc);
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || (function () {
    var ownKeys = function(o) {
        ownKeys = Object.getOwnPropertyNames || function (o) {
            var ar = [];
            for (var k in o) if (Object.prototype.hasOwnProperty.call(o, k)) ar[ar.length] = k;
            return ar;
        };
        return ownKeys(o);
    };
    return function (mod) {
        if (mod && mod.__esModule) return mod;
        var result = {};
        if (mod != null) for (var k = ownKeys(mod), i = 0; i < k.length; i++) if (k[i] !== "default") __createBinding(result, mod, k[i]);
        __setModuleDefault(result, mod);
        return result;
    };
})();
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = require("express");
const conversation_1 = require("../services/conversation");
const whatsapp_1 = require("../services/whatsapp");
const logger = __importStar(require("../services/logger"));
const router = (0, express_1.Router)();
router.get('/webhook', (req, res) => {
    const mode = req.query['hub.mode'];
    const token = req.query['hub.verify_token'];
    const challenge = req.query['hub.challenge'];
    if (mode === 'subscribe' && token === process.env.WHATSAPP_VERIFY_TOKEN) {
        res.status(200).send(challenge);
    }
    else {
        res.sendStatus(403);
    }
});
router.post('/webhook', async (req, res) => {
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
            let imageId = null;
            if (messageType === 'image' && message.image?.id) {
                imageId = message.image.id;
            }
            // Masquer le numéro pour la confidentialité
            const maskedPhone = from.substring(0, 4) + '****' + from.substring(from.length - 2);
            await logger.log('info', 'webhook', `Message de ${maskedPhone} (${messageType})`);
            try {
                const responses = await (0, conversation_1.processMessage)(from, text || '', messageType, imageId);
                for (const response of responses) {
                    await (0, whatsapp_1.sendMessage)(from, response);
                }
            }
            catch (error) {
                await logger.log('error', 'webhook', `Erreur traitement message: ${maskedPhone}`, error);
            }
        }
    }
    else {
        res.sendStatus(200);
    }
});
exports.default = router;
