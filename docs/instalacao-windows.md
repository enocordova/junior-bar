# Instalação no Windows — Passo a Passo Completo

Este guia parte do zero: um Windows com acesso à internet, sem nada instalado.

---

## Passo 1 — Verificar se o Windows é compatível

O sistema precisa do **Windows 10 versão 2004 ou superior** (ou Windows 11).

Para verificar:
1. Prima as teclas **Windows + R**
2. Escreve `winver` e prime **Enter**
3. Confirma que a versão é **2004, 20H2, 21H1** ou superior (ou Windows 11)

Se a versão for mais antiga, faz as atualizações do Windows antes de continuar.

---

## Passo 2 — Ativar a virtualização no BIOS (se necessário)

O Docker precisa de virtualização ativa. Na maioria dos computadores já está ativa, mas se tiveres problemas no Passo 4, volta aqui.

Para verificar se está ativa:
1. Prime **Ctrl + Shift + Esc** para abrir o Gestor de Tarefas
2. Vai ao separador **Desempenho** → **CPU**
3. Procura a linha **Virtualização: Ativada**

Se aparecer **Desativada**, tens de entrar no BIOS do teu computador e ativá-la (consulta o manual do fabricante ou pesquisa "ativar virtualização [marca do teu PC]").

---

## Passo 3 — Instalar o WSL2 (Subsistema Linux para Windows)

1. Clica no botão **Iniciar** do Windows
2. Pesquisa por **PowerShell**, clica com o botão direito e escolhe **"Executar como Administrador"**
3. Confirma o aviso de segurança clicando em **Sim**
4. Na janela preta que aparece, escreve o seguinte e prime **Enter**:

```powershell
wsl --install
```

5. Aguarda o processo terminar (pode demorar alguns minutos, vai descarregar o Ubuntu)
6. Quando terminar, **reinicia o computador**

> **O que isto faz:** instala o WSL2 e o Ubuntu automaticamente. O Ubuntu é o sistema Linux que vais usar para correr o sistema JuniorBar.

---

## Passo 4 — Instalar o Docker Desktop

1. Abre o navegador e vai a: **https://www.docker.com/get-started/**
2. Clica em **"Download Docker Desktop for Windows"**
3. Abre o ficheiro descarregado (`Docker Desktop Installer.exe`)
4. No instalador, certifica-te que a opção **"Use WSL 2 instead of Hyper-V"** está marcada
5. Clica em **OK** e aguarda a instalação terminar
6. Quando pedir para reiniciar, **reinicia o computador**
7. Após reiniciar, o Docker Desktop vai abrir automaticamente
8. Aceita os termos de serviço se pedido
9. Aguarda até ao ícone da baleia 🐳 na barra de tarefas (canto inferior direito) ficar estável — pode demorar 1-2 minutos

> Se aparecer uma mensagem sobre "WSL 2 kernel update", clica no link fornecido, descarrega e instala a atualização, depois reinicia o Docker Desktop.

---

## Passo 5 — Abrir o terminal Ubuntu

1. Clica no botão **Iniciar** do Windows
2. Pesquisa por **Ubuntu**
3. Abre a aplicação **Ubuntu**

> Na primeira abertura, o Ubuntu pode pedir para criares um **utilizador e senha** para o Linux. Usa um nome simples (ex: `admin`) e uma senha que não te esqueças. Esta senha é só para o Linux no teu computador.

Deverás ver um terminal com o cursor a piscar, parecido com:
```
nome@NomeDoPC:~$
```

---

## Passo 6 — Instalar as ferramentas necessárias

No terminal Ubuntu, copia e cola o seguinte comando e prime **Enter**:

```bash
sudo apt update && sudo apt install -y make git openssl
```

Vai pedir a senha que criaste no Passo 5. Escreve-a (não vais ver os caracteres enquanto escreves — é normal) e prime **Enter**.

Aguarda terminar. Deve aparecer no final algo como `done`.

---

## Passo 7 — Descarregar o sistema JuniorBar

No mesmo terminal Ubuntu, cola o seguinte e prime **Enter**:

```bash
git clone https://github.com/enocordova/junior-bar.git juniorbar
cd juniorbar
```

Isto vai criar uma pasta chamada `juniorbar` com todos os ficheiros do sistema.

---

## Passo 8 — Configurar e iniciar o sistema

Ainda no terminal Ubuntu, dentro da pasta `juniorbar`, corre:

```bash
./setup.sh
```

O script vai fazer tudo automaticamente:

1. Vai detetar o IP do computador na rede e mostrar para confirmar — prima **Enter** para aceitar
2. Vai perguntar se quer iniciar o sistema agora — prima **Enter** para confirmar
3. Vai descarregar os componentes necessários (Docker vai descarregar as imagens — **pode demorar 5 a 10 minutos na primeira vez**)
4. No final, vai mostrar os endereços e as senhas de acesso

**Exemplo do que vais ver no final:**

```
  Acesse em qualquer dispositivo na mesma rede Wi-Fi:

  Painel admin:  https://192.168.1.X:8443/admin
  Garçom:        https://192.168.1.X:8443/garcom
  Cozinha (KDS): https://192.168.1.X:8443/cozinha
  Gerente:       https://192.168.1.X:8443/gerente

  Credenciais de acesso
  Admin/Gerente  login: admin    senha: AbCdEf123456
  Garçom         login: garcom   senha: GhIjKl789012
  Cozinha        login: cozinha  senha: MnOpQr345678
```

> **Guarda as senhas!** Anota-as ou tira uma fotografia ao ecrã. Ficam também guardadas no ficheiro `.env` dentro da pasta `juniorbar`.

---

## Passo 9 — Aceder ao sistema

Abre o **navegador** (Chrome, Edge, Firefox) no próprio computador ou em qualquer telemóvel/tablet ligado à **mesma rede Wi-Fi** e vai ao endereço mostrado pelo setup, por exemplo:

```
https://192.168.1.X:8443/login
```

**Aviso de certificado:** O navegador vai mostrar um alerta de segurança. Isto é normal — o certificado é gerado localmente. Clica em:
- **Chrome/Edge:** "Avançado" → "Continuar para o site (não seguro)"
- **Firefox:** "Avançado" → "Aceitar o risco e continuar"
- **Safari (iPhone):** "Visitar este website" → "Visitar website"

---

## Uso diário

Sempre que ligares o computador, o sistema **sobe automaticamente** com o Docker Desktop.

Se precisares de parar ou reiniciar manualmente, abre o terminal Ubuntu e vai à pasta do projeto:

```bash
cd juniorbar
make up       # iniciar
make down     # parar
make ps       # ver se está tudo a correr
```

---

## Problemas comuns

**"Docker Desktop não abre" ou "WSL 2 installation is incomplete"**

Abre o PowerShell como Administrador e corre:
```powershell
wsl --update
```
Depois reinicia o Docker Desktop.

---

**"permission denied" ao correr `./setup.sh`**

No terminal Ubuntu:
```bash
chmod +x setup.sh
./setup.sh
```

---

**O sistema abre mas os outros dispositivos (telemóvel) não conseguem aceder**

Confirma que o telemóvel está ligado à **mesma rede Wi-Fi** que o computador.
Se ainda não funcionar, o Windows Firewall pode estar a bloquear. No PowerShell como Administrador:
```powershell
New-NetFirewallRule -DisplayName "JuniorBar" -Direction Inbound -Protocol TCP -LocalPort 8443 -Action Allow
```

---

**O IP mudou (ligou a outro Wi-Fi ou reiniciou o router)**

No terminal Ubuntu, dentro da pasta `juniorbar`:
```bash
./setup.sh
```
O script deteta o novo IP e reconfigura tudo automaticamente.
