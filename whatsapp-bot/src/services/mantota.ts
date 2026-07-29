import axios from 'axios';
import pool from '../db/connection';
import * as logger from './logger';

const MANTOTA_API_URL = process.env.MANTOTA_API_URL || 'https://mantota.com/api';
const MANTOTA_API_SECRET = process.env.MANTOTA_API_SECRET;

// Interfaces pour les commandes
export interface OrderData {
  vendor_id: number;
  product_id: number;
  customer_name: string;
  customer_phone: string;
  customer_whatsapp: string;
  customer_email?: string;
  country: string;
  city: string;
  landmark_indication?: string;
}

export interface OrderResult {
  success: boolean;
  order_id: number;
  reference: string;
  payment_url: string;
  tracking_url: string;
  tracking_token: string;
  delivery_pin: string;
  amount: number;
}

export interface OrderStatus {
  order_id: number;
  reference: string;
  status: string;
  payment_status: string;
  customer_name: string;
  product_name: string;
  amount_paid: number;
  tracking_url: string;
  delivery_guy_name: string | null;
  delivery_guy_phone: string | null;
  delivery_company: string | null;
  is_overdue: boolean;
}

export interface VendorStats {
  this_month: {
    total_orders: number;
    pending: number;
    shipped: number;
    delivered: number;
    disputed: number;
    cancelled: number;
  };
  revenue: {
    available: number;
    in_escrow: number;
    earned_this_month: number;
  };
  top_products: {
    name: string;
    orders: number;
    revenue: number;
  }[];
}

// Génère un code court unique pour la boutique
export function generateShortCode(shopName: string): string {
  // Prendre les 2 premières lettres en majuscules
  let prefix = shopName.length >= 2 
    ? shopName.substring(0, 2).toUpperCase() 
    : 'OD';
  
  // Générer un nombre aléatoire de 3 chiffres (001-999)
  const randomNum = Math.floor(Math.random() * 999) + 1;
  const suffix = randomNum.toString().padStart(3, '0');
  
  return prefix + suffix;
}

export async function createVendor(data: {
  name: string;
  email: string;
  password: string;
  phone: string;
  shop_name: string;
  shop_type: string;
  shop_address?: string;
  shop_latitude?: number;
  shop_longitude?: number;
}): Promise<{ id: number; slug: string; short_code: string }> {
  try {
    const response = await axios.post(
      `${MANTOTA_API_URL}/bot/vendors`,
      {
        ...data,
        manual_access: false,
      },
      {
        headers: {
          'X-Bot-Api-Key': MANTOTA_API_SECRET,
          'Content-Type': 'application/json',
        },
      }
    );

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
      const [existingRows] = await pool.query(
        'SELECT id FROM vendors WHERE short_code = ?',
        [code]
      ) as any[];
      
      if (existingRows.length === 0) {
        // Code unique, sauvegarder
        await pool.query(
          'UPDATE vendors SET short_code = ? WHERE id = ?',
          [code, vendorResult.id]
        );
        break;
      }
      
      // Code existe, régénérer
      code = generateShortCode(data.shop_name);
      attempts++;
    }
    
    // Si après 10 tentatives toujours pas unique, utiliser timestamp
    if (attempts >= maxAttempts) {
      code = 'OD' + Date.now().toString().substring(8);
      await pool.query(
        'UPDATE vendors SET short_code = ? WHERE id = ?',
        [code, vendorResult.id]
      );
    }
    
    return { ...vendorResult, short_code: code };
  } catch (error) {
    await logger.log('error', 'mantota', 'Erreur création vendor Mantota', error);
    throw error;
  }
}

export async function createProduct(data: {
  vendor_id: number;
  name: string;
  type: string;
  description: string;
  price: number;
  image_url?: string;
  categorie?: string;
}): Promise<{ id: number }> {
  try {
    const response = await axios.post(
      `${MANTOTA_API_URL}/bot/products`,
      data,
      {
        headers: {
          'X-Bot-Api-Key': MANTOTA_API_SECRET,
          'Content-Type': 'application/json',
        },
      }
    );

    return {
      id: response.data.product_id,
    };
  } catch (error) {
    await logger.log('error', 'mantota', 'Erreur création produit Mantota', error);
    throw error;
  }
}

export async function updateProduct(productId: number, data: {
  name?: string;
  price?: number;
  description?: string;
  image_url?: string;
  categorie?: string;
}): Promise<void> {
  try {
    await axios.put(
      `${MANTOTA_API_URL}/bot/products/${productId}`,
      data,
      {
        headers: {
          'X-Bot-Api-Key': MANTOTA_API_SECRET,
          'Content-Type': 'application/json',
        },
      }
    );
  } catch (error) {
    await logger.log('error', 'mantota', 'Erreur mise à jour produit Mantota', error);
    throw error;
  }
}

export async function getVendorProducts(vendorId: number): Promise<any[]> {
  try {
    const response = await axios.get(
      `${MANTOTA_API_URL}/bot/vendors/${vendorId}/products`,
      {
        headers: {
          'X-Bot-Api-Key': MANTOTA_API_SECRET,
        },
      }
    );

    return response.data.products || [];
  } catch (error) {
    await logger.log('error', 'mantota', 'Erreur récupération produits Mantota', error);
    return [];
  }
}

// Créer une commande depuis Tracy
export async function createOrder(data: OrderData): Promise<OrderResult> {
  try {
    const response = await axios.post(
      `${MANTOTA_API_URL}/api/bot/orders`,
      data,
      {
        headers: {
          'X-Bot-Api-Key': MANTOTA_API_SECRET,
          'Content-Type': 'application/json',
        },
      }
    );

    return response.data as OrderResult;
  } catch (error) {
    await logger.log('error', 'mantota', 'Erreur création commande', error);
    throw error;
  }
}

// Obtenir le statut d'une commande par référence
export async function getOrderStatus(reference: string): Promise<OrderStatus | null> {
  try {
    const response = await axios.get(
      `${MANTOTA_API_URL}/api/bot/orders/${reference}`,
      {
        headers: {
          'X-Bot-Api-Key': MANTOTA_API_SECRET,
        },
      }
    );

    return response.data as OrderStatus;
  } catch (error: any) {
    if (error.response?.status === 404) {
      return null;
    }
    await logger.log('error', 'mantota', 'Erreur récupération statut commande', error);
    return null;
  }
}

// Obtenir les statistiques d'un vendeur
export async function getVendorStats(vendorId: number): Promise<VendorStats | null> {
  try {
    const response = await axios.get(
      `${MANTOTA_API_URL}/api/bot/vendors/${vendorId}/stats`,
      {
        headers: {
          'X-Bot-Api-Key': MANTOTA_API_SECRET,
        },
      }
    );

    return response.data as VendorStats;
  } catch (error) {
    await logger.log('error', 'mantota', 'Erreur récupération stats vendeur', error);
    return null;
  }
}
