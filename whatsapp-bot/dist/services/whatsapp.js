"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.sendMessage = sendMessage;
const axios_1 = __importDefault(require("axios"));
async function sendMessage(to, text) {
    const phoneNumberId = process.env.WHATSAPP_PHONE_NUMBER_ID;
    const token = process.env.WHATSAPP_TOKEN;
    if (!phoneNumberId || !token) {
        throw new Error('WHATSAPP_PHONE_NUMBER_ID ou WHATSAPP_TOKEN non défini');
    }
    const url = `https://graph.facebook.com/v20.0/${phoneNumberId}/messages`;
    try {
        await axios_1.default.post(url, {
            messaging_product: 'whatsapp',
            to,
            type: 'text',
            text: { body: text },
        }, {
            headers: {
                Authorization: `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
        });
    }
    catch (error) {
        console.error('Erreur envoi message WhatsApp:', error);
    }
}
