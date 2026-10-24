# Lume Pet

A Lume Pet é uma marca fictícia criada para representar a plataforma digital desenvolvida neste trabalho de conclusão de curso.

Site em PHP contém catálogo, conta de cliente, painel administrativo e
cadastro de produtos, ONGs e animais.

## Desenvolvedoras

- Lorena Gomes 
- Maria Carolina 
- Maria Eduarda 
- Mariana Couto
- Stella Zambianco

## Requisitos

- PHP 8.0 ou superior, com PDO MySQL, `mbstring`, `curl` e `openssl`
- MySQL ou MariaDB
- Apache (XAMPP é suportado para desenvolvimento local)

## Executar localmente com XAMPP

1. Coloque o projeto em `C:\xampp\htdocs\Site-Lume-Pet`.
2. Inicie Apache e MySQL pelo painel do XAMPP.
3. No phpMyAdmin, importe `database/tabelas.sql`.
4. Se o banco já existe, selecione `lume_pet` no phpMyAdmin, abra **SQL** e execute `database/migrations/avaliacoes.sql`, `database/migrations/acompanhamento_pedidos.sql`, `database/migrations/recuperacao_senha.sql`, `database/migrations/verificacao_email.sql` e `database/migrations/cartoes_enderecos.sql`.
5. Importe `database/mensagens_contato.sql` e `database/seeds/admins.sql`.
6. Configure o banco no ambiente ou confira os valores locais em
   `config/database.php`.
7. Abra `http://localhost/Site-Lume-Pet/`.

`BASE_URL` é calculada a partir do caminho do projeto dentro do document root,
para permitir que o projeto rode em uma subpasta ou diretamente na raiz do
servidor web.

Em produção, configure `LUME_PET_DB_HOST`, `LUME_PET_DB_NAME`,
`LUME_PET_DB_USER`, `LUME_PET_DB_PASS` e, se necessário,
`LUME_PET_DB_CHARSET` como variáveis de ambiente.

Para enviar links de recuperação de senha, crie uma chave de API na Resend,
valide o domínio do remetente no serviço e configure
`LUME_PET_RESEND_API_KEY` com a chave privada e `LUME_PET_MAIL_FROM` com o
endereço remetente validado. Nunca coloque a chave no código ou no Git.
Defina também `LUME_PET_PUBLIC_URL` com a URL pública completa do site
(por exemplo, `https://exemplo.com` ou `http://localhost/Site-Lume-Pet`).
O cadastro e o acesso à conta não exigem confirmação de e-mail. Os links de
recuperação expiram em uma hora e só podem ser usados uma vez. O painel
administrativo **Contas de clientes** mantém os registros históricos de
confirmação, sem bloquear contas sem confirmação.

Os cadastros de cartão deste projeto são apenas demonstrativos: não há
operadora conectada, portanto nenhum cartão pode ser cobrado. Não digite dados
reais. No checkout e nas doações, um cartão salvo só identifica a modalidade
escolhida e deixa o pedido/doação pendente para conferência externa.

O schema não inclui dados de demonstração; os administradores iniciais ficam
separados em `database/seeds/admins.sql`. O seed pode ser importado novamente
sem duplicar contas pelo e-mail.

## Estrutura atual

As páginas e áreas privadas estão agrupadas por responsabilidade. Os links
internos usam `BASE_URL` e refletem esta estrutura:

```text
/
├── index.php                 Loja e catálogo
├── conta.php                 Roteia a conta conforme o tipo da sessão
├── includes/                 Autenticação, utilitários e layouts PHP
├── administrador/
│   ├── produtos.php          Gerenciamento administrativo de produtos
│   ├── avaliacoes.php        Consulta e remoção administrativa de avaliações
│   ├── emails.php            Registros históricos de confirmação de e-mail
│   └── animais.php           Gerenciamento administrativo de animais
├── usuario/
│   ├── dashboard.php         Resumo da conta do cliente
│   ├── perfil.php            Dados e endereços salvos do cliente
│   ├── compras.php           Compras do cliente
│   ├── doacoes.php           Doações do cliente
│   ├── pontos.php            Saldo e histórico de pontos
│   ├── cartoes.php           Cartões salvos e modalidade de uso
│   ├── avaliacoes.php        Histórico de avaliações do cliente
│   └── ongs.php              Gerenciamento administrativo de ONGs
├── pages/
│   ├── ongpage.php           Informações das ONGs parceiras
│   ├── politica.php          Política de privacidade
│   ├── recuperar.php         Recuperação de senha
│   └── verificar-email.php   Página legada de confirmação de e-mail
├── api/                      Endpoints JSON de login, pedidos, cartões e doações
├── config/                   Configuração de banco e URL base
├── assets/
│   └── images/               Imagens estáticas do site e da interface
├── css/                      Folhas de estilo
├── js/
│   ├── auth.js               Login, cadastro, conta e sessão visual
│   ├── commerce.js           Carrinho, checkout e pagamentos
│   ├── pedidos.js            Histórico compartilhado e importação de pedidos
│   ├── site.js               Catálogo, carrosséis, contato e avaliações
│   └── cep-perfil.js         Busca de endereço por CEP no perfil do cliente
├── database/                 Schema e scripts SQL
└── uploads/                  Imagens enviadas pelo painel
```

## Sessões e áreas privadas

- `conta.php` valida a sessão no servidor e direciona clientes para
  `usuario/dashboard.php` e administradores para `includes/index.php`.
- `includes/auth.php` concentra a autenticação e as verificações de acesso.
- `includes/functions.php` concentra as funções comuns. `functions.php` na
  raiz apenas encaminha para a implementação compartilhada.
- As imagens enviadas ficam em `uploads/`; os subdiretórios são criados quando
  um arquivo é enviado.
- `js/` usa scripts clássicos carregados na ordem `pedidos.js`, `auth.js`,
  `commerce.js`, `site.js`; essa ordem mantém disponíveis as funções usadas
  pelos handlers HTML e o estado compartilhado da sessão.

## Observações para manutenção

- Use sempre `BASE_URL` nos links, redirects e assets PHP; não fixe
  `/lume_pet`, nomes de pastas em maiúsculas ou caminhos relativos à URL atual.
- Mantenha a ordem de carregamento dos scripts e as funções chamadas pelos
  handlers HTML ao adicionar novas interações.
- Avaliações ficam salvas no banco, vinculadas ao cliente e ao produto. Clientes
  podem consultá-las na conta; administradores podem removê-las no painel.
- `api/finalizar-compra.php` armazena os pedidos e oferece o histórico comum ao
  modal e à conta. Para bancos existentes, execute
  `database/migrations/acompanhamento_pedidos.sql` pelo phpMyAdmin antes de
  utilizar o checkout.
- O histórico antigo do navegador é importado uma única vez para a conta ativa.
  Como os registros antigos não têm dados verificáveis de cobrança, eles são
  marcados como pendentes e não como pagamentos confirmados. O checkout também
  permanece simulado até a integração de um gateway; nenhum dado de cartão é
  salvo.


## FINALIZAÇÃO DO SITE: ......