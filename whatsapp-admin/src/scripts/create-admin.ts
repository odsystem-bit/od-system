import bcrypt from 'bcryptjs';
import pool from '../db/connection';
import dotenv from 'dotenv';

dotenv.config();

async function createAdmin(email: string, password: string): Promise<void> {
  try {
    const hashedPassword = await bcrypt.hash(password, 12);

    await pool.query(
      'INSERT INTO admin_users (email, password_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE password_hash = ?',
      [email, hashedPassword, hashedPassword]
    );

    console.log(`✅ Compte admin créé/mis à jour : ${email}`);
  } catch (error) {
    console.error('❌ Erreur création admin:', error);
    process.exit(1);
  }
}

const email = process.argv[2];
const password = process.argv[3];

if (!email || !password) {
  console.log('Usage: npm run create-admin <email> <password>');
  process.exit(1);
}

createAdmin(email, password).then(() => process.exit(0));
