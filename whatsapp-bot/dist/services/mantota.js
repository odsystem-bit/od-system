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
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.generateShortCode = generateShortCode;
exports.createVendor = createVendor;
exports.createProduct = createProduct;
exports.updateProduct = updateProduct;
exports.getVendorProducts = getVendorProducts;
exports.createOrder = createOrder;
exports.getOrderStatus = getOrderStatus;
exports.getVendorStats = getVendorStats;
const axios_1 = __importDefault(require("axios"));
const connection_1 = __importDefault(require("../db/connection"));
const logger = __importStar(require("./logger"));
const MANTOTA_API_URL = process.env.MANTOTA_API_URL || 'https://mantota.com/api';
const MANTOTA_API_SECRET = process.env.MANTOTA_API_SECRET;
// Génère un code court unique pour la boutique
function generateShortCode(shopName) {
    // Prendre les 2 premières lettres en majuscules
    let prefix = shopName.length >= 2
        ? shopName.substring(0, 2).toUpperCase()
        : 'OD';
    // Générer un nombre aléatoire de 3 chiffres (001-999)
    const randomNum = Math.floor(Math.random() * 999) + 1;
    const suffix = randomNum.toString().padStart(3, '0');
    return prefix + suffix;
}
async function createVendor(data) {
    try {
        const response = await axios_1.default.post(`${MANTOTA_API_URL}/bot/vendors`, {
            ...data,
            manual_access: false,
        }, {
            headers: {
                'X-Bot-Api-Key': MANTOTA_API_SECRET,
                'Content-Type': 'application/json',
            },
        });
        await logger.log('info', 'mantota', `Vendeur créé: ${response.data.vendor_id}`);
        const vendorResult = {
            id: response.data.vendor_id,
            slug: response.data.shop_url?.split('/').pop() || '',
        };
        // Générer un short_code unique
        let code = generateShortCode(data.shop_name);
        let attempts = 0;
        const maxAttempts = 10;
        // Vérifier que le code n'existe pas déjà
        while (attempts < maxAttempts) {
            const [existingRows] = await connection_1.default.query('SELECT id FROM vendors WHERE short_code = ?', [code]);
            if (existingRows.length === 0) {
                // Code unique, sauvegarder
                await connection_1.default.query('UPDATE vendors SET short_code = ? WHERE id = ?', [code, vendorResult.id]);
                break;
            }
            // Code existe, régénérer
            code = generateShortCode(data.shop_name);
            attempts++;
        }
        // Si après 10 tentatives toujours pas unique, utiliser timestamp
        if (attempts >= maxAttempts) {
            code = 'OD' + Date.now().toString().substring(8);
            await connection_1.default.query('UPDATE vendors SET short_code = ? WHERE id = ?', [code, vendorResult.id]);
        }
        return { ...vendorResult, short_code: code };
    }
    catch (error) {
        await logger.log('error', 'mantota', 'Erreur création vendor Mantota', error);
        throw error;
    }
}
async function createProduct(data) {
    try {
        const response = await axios_1.default.post(`${MANTOTA_API_URL}/bot/products`, data, {
            headers: {
                'X-Bot-Api-Key': MANTOTA_API_SECRET,
                'Content-Type': 'application/json',
            },
        });
        return {
            id: response.data.product_id,
        };
    }
    catch (error) {
        await logger.log('error', 'mantota', 'Erreur création produit Mantota', error);
        throw error;
    }
}
async function updateProduct(productId, data) {
    try {
        await axios_1.default.put(`${MANTOTA_API_URL}/bot/products/${productId}`, data, {
            headers: {
                'X-Bot-Api-Key': MANTOTA_API_SECRET,
                'Content-Type': 'application/json',
            },
        });
    }
    catch (error) {
        await logger.log('error', 'mantota', 'Erreur mise à jour produit Mantota', error);
        throw error;
    }
}
async function getVendorProducts(vendorId) {
    try {
        const response = await axios_1.default.get(`${MANTOTA_API_URL}/bot/vendors/${vendorId}/products`, {
            headers: {
                'X-Bot-Api-Key': MANTOTA_API_SECRET,
            },
        });
        return response.data.products || [];
    }
    catch (error) {
        await logger.log('error', 'mantota', 'Erreur récupération produits Mantota', error);
        return [];
    }
}
// Créer une commande depuis Tracy
async function createOrder(data) {
    try {
        const response = await axios_1.default.post(`${MANTOTA_API_URL}/api/bot/orders`, data, {
            headers: {
                'X-Bot-Api-Key': MANTOTA_API_SECRET,
                'Content-Type': 'application/json',
            },
        });
        return response.data;
    }
    catch (error) {
        await logger.log('error', 'mantota', 'Erreur création commande', error);
        throw error;
    }
}
// Obtenir le statut d'une commande par référence
async function getOrderStatus(reference) {
    try {
        const response = await axios_1.default.get(`${MANTOTA_API_URL}/api/bot/orders/${reference}`, {
            headers: {
                'X-Bot-Api-Key': MANTOTA_API_SECRET,
            },
        });
        return response.data;
    }
    catch (error) {
        if (error.response?.status === 404) {
            return null;
        }
        await logger.log('error', 'mantota', 'Erreur récupération statut commande', error);
        return null;
    }
}
// Obtenir les statistiques d'un vendeur
async function getVendorStats(vendorId) {
    try {
        const response = await axios_1.default.get(`${MANTOTA_API_URL}/api/bot/vendors/${vendorId}/stats`, {
            headers: {
                'X-Bot-Api-Key': MANTOTA_API_SECRET,
            },
        });
        return response.data;
    }
    catch (error) {
        await logger.log('error', 'mantota', 'Erreur récupération stats vendeur', error);
        return null;
    }
}
