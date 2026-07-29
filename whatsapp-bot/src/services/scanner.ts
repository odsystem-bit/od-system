import puppeteer from 'puppeteer';
import axios from 'axios';
import * as logger from './logger';

export interface Product {
  nom: string;
  prix: number;
  description: string;
  categorie: string | null;
}

export interface CompareResult {
  ajouts: Product[];
  suppressions: Product[];
  modifications: { ancien: Product; nouveau: Product }[];
  hasChanges: boolean;
}

async function callGPTVision(base64Image: string, model: string): Promise<Product[]> {
  const apiKey = process.env.OPENAI_API_KEY;
  if (!apiKey) {
    await logger.log('error', 'scanner', 'OPENAI_API_KEY non défini');
    return [];
  }

  const response = await axios.post(
    'https://api.openai.com/v1/chat/completions',
    {
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
    },
    {
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
      },
    }
  );

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

export async function scanSite(url: string): Promise<Product[]> {
  let browser: any = null;
  
  try {
    // Lancer Puppeteer en mode headless
    browser = await puppeteer.launch({
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
  } catch (error) {
    await logger.log('error', 'scanner', `Erreur scan site: ${url}`, error);
    return [];
  } finally {
    // Fermer toujours le navigateur pour éviter les fuites mémoire
    if (browser) {
      await browser.close();
    }
  }
}

export function compareProducts(
  anciens: Product[],
  nouveaux: Product[]
): CompareResult {
  const anciensMap = new Map(
    anciens.map(p => [p.nom.toLowerCase().trim(), p])
  );
  const nouveauxMap = new Map(
    nouveaux.map(p => [p.nom.toLowerCase().trim(), p])
  );

  const ajouts: Product[] = [];
  const suppressions: Product[] = [];
  const modifications: { ancien: Product; nouveau: Product }[] = [];

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

export function formatChangesMessage(result: CompareResult): string {
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
