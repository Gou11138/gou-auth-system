# 🚀 Sistema de Autenticação Remota - Vercel (GRATUITO)

Guia completo para hospedar seu sistema de autenticação no Vercel **100% GRATUITO**!

## 🎯 Por que Vercel?

✅ **Totalmente gratuito**
✅ **Deploy automático**
✅ **SSL automático**
✅ **Domínio gratuito**
✅ **Muito rápido**
✅ **Confiável**

## 📋 Pré-requisitos

- Conta no GitHub (gratuita)
- Conta no Vercel (gratuita)
- Git instalado no PC

## 🚀 Passo a Passo Completo

### 1. Criar Conta no GitHub

1. Vá para [github.com](https://github.com)
2. Clique em "Sign up"
3. Crie sua conta gratuita

### 2. Criar Repositório no GitHub

1. No GitHub, clique em "New repository"
2. Nome: `gou-auth-system`
3. Marque como "Public"
4. Clique em "Create repository"

### 3. Fazer Upload dos Arquivos

1. No repositório criado, clique em "uploading an existing file"
2. Arraste todos os arquivos da pasta `vercel_auth_system/`:
   - `api/index.php`
   - `vercel.json`
   - `README_VERCEL.md`

### 4. Criar Conta no Vercel

1. Vá para [vercel.com](https://vercel.com)
2. Clique em "Sign up"
3. Escolha "Continue with GitHub"
4. Autorize o Vercel

### 5. Deploy Automático

1. No Vercel, clique em "New Project"
2. Escolha o repositório `gou-auth-system`
3. Clique em "Deploy"

### 6. Configurar Domínio

1. Após o deploy, você terá um domínio como: `gou-auth-system.vercel.app`
2. Para domínio personalizado:
   - Vá em "Settings" > "Domains"
   - Adicione seu domínio (se tiver)

## 🔧 Configurar o Código C++

### Editar RemoteAuth.hpp

1. Abra o arquivo: `Source Goulart/src/Security/Api/RemoteAuth.hpp`
2. Altere a linha 18:

```cpp
// Para Vercel
const std::string API_BASE_URL = "https://gou-auth-system.vercel.app/api/";

// OU se tiver domínio personalizado
const std::string API_BASE_URL = "https://seudominio.com/api/";
```

## 📊 URLs do Sistema

- **API Principal**: `https://gou-auth-system.vercel.app/api/index.php`
- **Exemplo de uso**: `https://gou-auth-system.vercel.app/api/index.php?action=validate_key`

## 🛠️ Gerenciar o Sistema

### Gerar Chaves via API

```bash
curl -X POST https://gou-auth-system.vercel.app/api/index.php?action=generate_keys \
  -H "Content-Type: application/json" \
  -d '{"count": 5, "prefix": "GOU"}'
```

### Ver Estatísticas

```bash
curl https://gou-auth-system.vercel.app/api/index.php?action=get_stats
```

## 🔒 Segurança

### Vantagens do Vercel:
- ✅ HTTPS automático
- ✅ Proteção DDoS
- ✅ CDN global
- ✅ Backups automáticos
- ✅ Monitoramento 24/7

### Limitações:
- ⚠️ SQLite (não MySQL)
- ⚠️ Sem painel administrativo web
- ⚠️ Precisa usar API para gerenciar

## 🎯 Alternativas Mais Simples

### Opção 1: Railway ($5/mês)
- MySQL incluído
- Painel administrativo
- Deploy automático
- Sem limitações

### Opção 2: Heroku (Gratuito)
- PostgreSQL gratuito
- App "dorme" após 30 min
- 550 horas/mês

### Opção 3: Render (Gratuito)
- PostgreSQL gratuito
- Sem sleep mode
- Deploy automático

## 📱 Gerenciar via App Mobile

### Criar App Simples para Gerenciar

Você pode criar um app simples (Flutter/React Native) para:
- Gerar chaves
- Ver estatísticas
- Gerenciar contas

## 🔄 Migração de Dados

### Se já tem dados locais:

1. **Exportar dados locais:**
   ```bash
   # Copiar chaves do keys.dat
   # Copiar contas do accounts.dat
   ```

2. **Importar via API:**
   ```bash
   # Gerar chaves via API
   # Recriar contas via API
   ```

## 🚨 Troubleshooting

### Erro de Deploy:
- Verifique se todos os arquivos estão no GitHub
- Confirme se o `vercel.json` está correto

### Erro de API:
- Teste a URL no navegador
- Verifique se o domínio está correto no código C++

### Erro de Banco:
- O SQLite é criado automaticamente
- Não precisa configurar nada

## 🎉 Benefícios Finais

✅ **100% gratuito**
✅ **Sem limitações de tráfego**
✅ **SSL automático**
✅ **Deploy automático**
✅ **Muito rápido**
✅ **Confiável**

## 📞 Suporte

- **Vercel Docs**: [vercel.com/docs](https://vercel.com/docs)
- **GitHub Issues**: Para problemas técnicos
- **Vercel Support**: Chat 24/7

---

**🎯 Resultado Final**: Sistema de autenticação remoto funcionando 100% gratuito! 🚀 