"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const express_1 = __importDefault(require("express"));
const dotenv_1 = __importDefault(require("dotenv"));
const webhook_1 = __importDefault(require("./routes/webhook"));
const admin_internal_1 = __importDefault(require("./routes/admin-internal"));
const cron_1 = require("./jobs/cron");
dotenv_1.default.config();
const app = (0, express_1.default)();
const port = process.env.PORT || 3000;
app.use(express_1.default.json());
app.use(express_1.default.urlencoded({ extended: true }));
app.use('/', webhook_1.default);
app.use('/api/admin', admin_internal_1.default);
app.listen(port, () => {
    console.log(`Serveur WhatsApp démarré sur le port ${port}`);
    (0, cron_1.startCronJobs)();
});
