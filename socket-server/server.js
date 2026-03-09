const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const cors = require('cors');

const BROADCAST_SECRET = process.env.BROADCAST_SECRET || 'mudar-em-producao';
const ALLOWED_ORIGIN = process.env.ALLOWED_ORIGIN || 'http://192.168.1.3:8080';

const app = express();
app.use(express.json());

const corsOrigin = ALLOWED_ORIGIN === '*' ? '*' : ALLOWED_ORIGIN.split(',');

// CORS restrito à origem da aplicação
app.use(cors({
    origin: corsOrigin,
    methods: ['GET', 'POST'],
}));

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: corsOrigin,
        methods: ["GET", "POST"],
    },
    pingTimeout: 60000,
});

// --- ROTA DE INTEGRAÇÃO (LARAVEL -> NODE) ---
// Protegida por token secreto partilhado
app.post('/api/broadcast', (req, res) => {
    const authHeader = req.headers['authorization'];

    if (!authHeader || authHeader !== `Bearer ${BROADCAST_SECRET}`) {
        return res.status(401).json({ error: 'Não autorizado' });
    }

    const { evento, dados } = req.body;

    if (evento && dados) {
        console.log(`Broadcast: ${evento}`);
        io.emit(evento, dados);
        return res.json({ success: true });
    }

    res.status(400).json({ error: 'Dados inválidos' });
});

// Health check
app.get('/health', (req, res) => {
    res.json({ status: 'ok', connections: io.engine.clientsCount });
});

io.on('connection', (socket) => {
    console.log(`Cliente conectado: ${socket.id}`);

    socket.on('disconnect', () => {
        console.log(`Desconectado: ${socket.id}`);
    });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, '0.0.0.0', () => {
    console.log(`KDS Server rodando na porta ${PORT}`);
});
