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

- PHP 8.0 ou superior, com PDO MySQL e `mbstring`
- MySQL ou MariaDB
- Apache (XAMPP é suportado para desenvolvimento local)

## Executar localmente com XAMPP

1. Coloque o projeto em `C:\xampp\htdocs\Site-Lume-Pet`.
2. Inicie Apache e MySQL pelo painel do XAMPP.
3. No phpMyAdmin, importe `database/tabelas.sql`.
4. Se o banco já existe, selecione `lume_pet` no phpMyAdmin, abra **SQL** e execute `database/migrations/avaliacoes.sql` e `database/migrations/acompanhamento_pedidos.sql`.
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

O schema não inclui dados de demonstração; os administradores iniciais ficam
separados em `database/seeds/admins.sql`. O seed pode ser importado novamente
sem duplicar contas pelo e-mail.

## Estrutura atual

As URLs públicas antigas permanecem na raiz para evitar quebrar links
existentes. O código compartilhado e as áreas privadas estão agrupados por
responsabilidade:

```text
/
├── index.php                 Loja e catálogo
├── conta.php                 Roteia a conta conforme o tipo da sessão
├── perfil.php                Dados do cliente
├── compras.php               Compras do cliente
├── doacoes.php               Doações do cliente
├── pontos.php                Saldo e histórico de pontos
├── cartoes.php               Cartões tokenizados
├── produtos.php              Gerenciamento administrativo de produtos
├── avaliacoes.php            Consulta e remoção administrativa de avaliações
├── ongs.php                  Gerenciamento administrativo de ONGs
├── animais.php               Gerenciamento administrativo de animais
├── usuario/
│   ├── dashboard.php         Resumo da conta do cliente
│   └── avaliacoes.php        Histórico de avaliações do cliente
├── api/                      Endpoints JSON usados pelo navegador
├── config/                   Configuração de banco e URL base
├── includes/                 Autenticação, utilitários e layouts PHP
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
  raiz apenas encaminha para a implementação compartilhada por compatibilidade.
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