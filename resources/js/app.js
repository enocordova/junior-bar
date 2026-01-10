import './bootstrap';
import Alpine from 'alpinejs';
import kdsSystem from './modules/cozinha';

// Importar e Expor o io globalmente
import { io } from "socket.io-client";
window.io = io;

window.Alpine = Alpine;
Alpine.data('kdsSystem', kdsSystem);
Alpine.start();