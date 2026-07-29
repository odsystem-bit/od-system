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
exports.scanSite = scanSite;
exports.compareProducts = compareProducts;
exports.formatChangesMessage = formatChangesMessage;
const puppeteer_1 = __importDefault(require("puppeteer"));
const axios_1 = __importDefault(require("axios"));
const logger = __importStar(require("./logger"));
async function callGPTVision(base64Image, model) {
    const apiKey = process.env.OPENAI_API_KEY;
    if (!apiKey) {
        await logger.log('error', 'scanner', 'OPENAI_API_KEY non défini');
        return [];
    }
    const response = await axios_1.default.post('https://api.openai.com/v1/chat/completions', {
        model: model,
        messages: [
            {
                role: 'system',
                content: "Tu es un assistant qui extrait les produits d'une page web depuis une capture d'écran. Retourne UNIQUEMENT un JSON valide sans markdown ni backticks :\n{\n  'products': [\n    {\n      'nom': string,\n      'prix': number (chiffres uniquement sans devise),\n      'description': string (max 200 caractères),\n      'categorie': string | null\n    }\n  ]\n}\nSi aucun produit visible, retourne {'products': []}.\nJamais autre chose que ce JSON."
            },
            {
                role: 'user',
                content: [
                    {
                        type: 'image_url',
                        image_url: {
                            url: base64Image,
                        },
                    },
                ],
            },
        ],
        max_tokens: 2000,
    }, {
        headers: {
            Authorization: `Bearer ${apiKey}`,
            'Content-Type': 'application/json',
        },
    });
    const content = response.data.choices[0]?.message?.content || '';
    // Parser le JSON retourné (strip markdown si présent)
    let jsonContent = content.trim();
    if (jsonContent.startsWith('```json')) {
        jsonContent = jsonContent.slice(7);
    }
    if (jsonContent.startsWith('```')) {
        jsonContent = jsonContent.slice(3);
    }
    if (jsonContent.endsWith('```')) {
        jsonContent = jsonContent.slice(0, -3);
    }
    jsonContent = jsonContent.trim();
    const parsed = JSON.parse(jsonContent);
    return parsed.products || [];
}
async function scanSite(url) {
    let browser = null;
    try {
        // Lancer Puppeteer en mode headless
        browser = await puppeteer_1.default.launch({
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
        });
        const page = await browser.newPage();
        // Configurer le viewport
        await page.setViewport({ width: 1280, height: 900 });
        // Naviguer vers l'URL avec timeout de 30 secondes
        await page.goto(url, {
            waitUntil: 'networkidle2',
            timeout: 30000
        });
        // Prendre une capture d'écran en JPEG qualité 80
        const screenshot = await page.screenshot({
            fullPage: false,
            encoding: 'base64',
            type: 'jpeg',
            quality: 80,
        });
        // Fermer le navigateur
        await browser.close();
        browser = null;
        // Convertir en base64 pour GPT Vision
        const base64Image = `data:image/jpeg;base64,${screenshot}`;
        // Appeler d'abord GPT-4o Mini Vision
        let products = await callGPTVision(base64Image, 'gpt-4o-mini');
        // Fallback vers GPT-4o si le résultat est vide
        if (products.length === 0) {
            await logger.log('warning', 'scanner', `Fallback GPT-4o pour ${url}`);
            products = await callGPTVision(base64Image, 'gpt-4o');
        }
        await logger.log('info', 'scanner', `Scan réussi: ${url}, ${products.length} produits trouvés`);
        return products;
    }
    catch (error) {
        await logger.log('error', 'scanner', `Erreur scan site: ${url}`, error);
        return [];
    }
    finally {
        // Fermer toujours le navigateur pour éviter les fuites mémoire
        if (browser) {
            await browser.close();
        }
    }
}
function compareProducts(anciens, nouveaux) {
    const anciensMap = new Map(anciens.map(p => [p.nom.toLowerCase().trim(), p]));
    const nouveauxMap = new Map(nouveaux.map(p => [p.nom.toLowerCase().trim(), p]));
    const ajouts = [];
    const suppressions = [];
    const modifications = [];
    // Détecter les ajouts
    for (const [key, nouveau] of nouveauxMap) {
        if (!anciensMap.has(key)) {
            ajouts.push(nouveau);
        }
    }
    // Détecter les suppressions
    for (const [key, ancien] of anciensMap) {
        if (!nouveauxMap.has(key)) {
            suppressions.push(ancien);
        }
    }
    // Détecter les modifications
    for (const [key, ancien] of anciensMap) {
        const nouveau = nouveauxMap.get(key);
        if (nouveau && (ancien.prix !== nouveau.prix || ancien.description !== nouveau.description)) {
            modifications.push({ ancien, nouveau });
        }
    }
    const hasChanges = ajouts.length > 0 || suppressions.length > 0 || modifications.length > 0;
    return {
        ajouts,
        suppressions,
        modifications,
        hasChanges,
    };
}
function formatChangesMessage(result) {
    if (!result.hasChanges) {
        return "✅ Catalogue déjà à jour !";
    }
    let message = "📊 Changements détectés sur ton site :\n\n";
    if (result.ajouts.length > 0) {
        message += `✅ Nouveaux (${result.ajouts.length}) :\n`;
        for (const produit of result.ajouts) {
            message += `   - ${produit.nom} à ${produit.prix} FCFA\n`;
        }
        message += "\n";
    }
    if (result.suppressions.length > 0) {
        message += `❌ Retirés (${result.suppressions.length}) :\n`;
        for (const produit of result.suppressions) {
            message += `   - ${produit.nom}\n`;
        }
        message += "\n";
    }
    if (result.modifications.length > 0) {
        message += `✏️ Modifiés (${result.modifications.length}) :\n`;
        for (const mod of result.modifications) {
            message += `   - ${mod.ancien.nom} : ${mod.ancien.prix} → ${mod.nouveau.prix} FCFA\n`;
        }
        message += "\n";
    }
    message += "Tu veux que je mette à jour ?\n";
    message += "1️⃣ Oui / 2️⃣ Non";
    return message;
}
