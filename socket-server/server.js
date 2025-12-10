const express = require('express');
const http = require('http');
const { Server } = require("socket.io");
const cors = require('cors');

const app = express();
app.use(cors());
app.use(express.json()); // Permite receber JSON do Laravel

const server = http.createServer(app);
const io = new Server(server, {
    cors: { origin: "*", methods: ["GET", "POST"] },
    pingTimeout: 60000,
});

// --- ROTA DE INTEGRAÇÃO (LARAVEL -> NODE) ---
// O Laravel chama esta rota quando algo acontece no Banco de Dados
app.post('/api/broadcast', (req, res) => {
    const { evento, dados } = req.body;
    
    if (evento && dados) {
        console.log(`📢 Broadcast Server-Side: ${evento}`);
        io.emit(evento, dados); // Reenvia para todos os navegadores conectados
        return res.json({ success: true });
    }
    
    res.status(400).json({ error: 'Dados inválidos' });
});

io.on('connection', (socket) => {
    console.log(`⚡ Cliente conectado: ${socket.id}`);
    
    socket.on('disconnect', () => {
        console.log(`❌ Desconectado: ${socket.id}`);
    });
});

const PORT = process.env.PORT || 3000;
server.listen(PORT, '0.0.0.0', () => { // '0.0.0.0' permite acesso pela rede Wi-Fi
    console.log(`🚀 KDS Server rodando na porta ${PORT}`);
});