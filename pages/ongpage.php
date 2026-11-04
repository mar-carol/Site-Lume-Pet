<?php require_once __DIR__ . '/../config/database.php'; ?>
<!DOCTYPE html> 
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossas ONGs parceiras | Lume Pet</title>
    <link rel="icon" href="<?= BASE_URL ?>/assets/images/imgicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= @filemtime(__DIR__ . '/../css/style.css') ?: time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body id="topo">

    <!-- Cabeçalho -->
    <header class="header ongs-header">
        <div class="ong-header-conteudo">
            <a href="<?= BASE_URL ?>/index.php" class="home-ong" aria-label="Voltar para a página inicial">
                <i class="fa-solid fa-house"></i>
            </a>
 
            <a href="<?= BASE_URL ?>/index.php" class="logo-header" aria-label="Voltar para a página inicial">
                <img src="<?= BASE_URL ?>/assets/images/marcad.png" alt="LumePet">
            </a>
        </div>
    </header>

    <main class="ongs-main">

        <!-- Hero -->
        <section class="ongs-hero">
            <div class="ongs-hero-overlay">
                <nav class="ongs-breadcrumbs" aria-label="Navegação de páginas">
                    <a href="<?= BASE_URL ?>/index.php">Início</a>
                    <span aria-hidden="true">&rsaquo;</span>
                    <strong>ONGs Parceiras</strong>
                </nav>
                <h1>Conheça nossas ONGs parceiras</h1>
                <p>Juntos, podemos fazer a diferença na vida de muitos animais e transformar as suas vidas. Conheça o trabalho das ONGs que dedicam suas vidas ao bem-estar animal e descubra como você pode fazer parte dessa causa.</p>
            </div>
        </section>

        <!-- Como ajudar -->
        <section class="ongs-secao" aria-labelledby="ongs-como-ajudar">
            <h2 class="ongs-titulo" id="ongs-como-ajudar">Como você pode fazer a diferença?</h2>
            <div class="ongs-passos">
                <article class="ongs-passo">
                    <span class="ongs-passo-icone"><i class="fa-solid fa-bag-shopping"></i></span>
                    <h3>Compre na LumePet</h3>
                    <p>Ao realizar uma compra na LumePet, 5% do valor é destinado às ONGs parceiras, ajudando a fortalecer o trabalho e a proteção animal.</p>
                </article>
                <article class="ongs-passo">
                    <span class="ongs-passo-icone"><i class="fa-solid fa-hand-holding-heart"></i></span>
                    <h3>Doe e transforme</h3>
                    <p>Na aba de doações, você pode contribuir com valores a partir de R$25,00 e apoiar as ONGs parceiras.</p>
                </article>
                <article class="ongs-passo">
                    <span class="ongs-passo-icone"><i class="fa-solid fa-paw"></i></span>
                    <h3>Adote com amor</h3>
                    <p>Conheça os animais disponíveis para adoção e preencha o formulário de interesse. Você pode dar a um animal lar cheio de carinho.</p>
                </article>
            </div>
        </section>
    
        <section class="ongs-acoes" aria-label="Ações rápidas">
            <a href="<?= BASE_URL ?>/index.php#doacao" class="btn-primary">Fazer Doação</a>
            <a href="<?= BASE_URL ?>/index.php#adote" class="btn-seg">Adotar um Pet</a>
        </section>

        <!-- ONGs parceiras -->
        <section class="ongs-secao" aria-labelledby="ongs-parceiras-titulo">
            <h2 class="ongs-titulo" id="ongs-parceiras-titulo">ONGs Parceiras</h2>
            <div class="ongs-grid">

                <article class="ongs-card" id="ong-viralata">
                    <div class="ongs-card-imagem">
                        <img src="<?= BASE_URL ?>/assets/images/viralataviraamg.jpeg" alt="ONG Vira Lata Vira Amigo" loading="lazy">
                    </div>
                    <div class="ongs-card-conteudo">
                        <h3>Vira Lata Vira Amigo</h3>
                        <p>A Vira Lata Vira Amigo atua no resgate, cuidado e proteção de cães em situação de abandono, oferecendo acolhimento e buscando novos lares para esses animais. Conheça o trabalho da ONG e descubra como você pode ajudar.</p>
                        <a href="https://viralataviraamigo.org.br/" target="_blank" rel="noopener noreferrer" class="btn-ong-site">Visitar site</a>
                    </div>
                </article>

                <article class="ongs-card" id="ong-viralata10">
                    <div class="ongs-card-imagem">
                        <img src="<?= BASE_URL ?>/assets/images/viralata10.jpg" alt="ONG Vira Lata é 10" loading="lazy">
                    </div>
                    <div class="ongs-card-conteudo">
                        <h3>Vira Lata é 10</h3>
                        <p>A Vira-Lata é 10 trabalha pela proteção de animais abandonados, promovendo adoção responsável, apadrinhamento e ações de conscientização sobre o cuidado e o respeito aos animais. Venha conhecer a ONG e os animais que esperam por um lar.</p>
                        <a href="https://www.ongviralataedez.com" target="_blank" rel="noopener noreferrer" class="btn-ong-site">Visitar site</a>
                    </div>
                </article>

                <article class="ongs-card" id="ong-salvagato">
                    <div class="ongs-card-imagem">
                        <img src="<?= BASE_URL ?>/assets/images/salvagato.jpg" alt="ONG Salva Gato" loading="lazy">
                    </div>
                    <div class="ongs-card-conteudo">
                        <h3>Salva Gato</h3>
                        <p>A SalvaGato é uma ONG voltada à proteção e ao bem-estar dos animais, atuando especialmente com gatos e iniciativas de adoção e cuidado. Conheça mais sobre seu trabalho e descubra maneiras de contribuir com essa causa.</p>
                        <a href="https://www.instagram.com/ongsalvagato" target="_blank" rel="noopener noreferrer" class="btn-ong-site">Ver Instagram</a>
                    </div>
                </article>

                <article class="ongs-card" id="ong-caramelo">
                    <div class="ongs-card-imagem">
                        <img src="<?= BASE_URL ?>/assets/images/caramelhoong.jpg" alt="ONG Instituto Caramelo" loading="lazy">
                    </div>
                    <div class="ongs-card-conteudo">
                        <h3>Instituto Caramelo</h3>
                        <p>O Instituto Caramelo atua no resgate, recuperação e adoção de animais feridos ou em situação de risco. Com um espaço dedicado ao cuidado de cães e gatos, busca oferecer proteção e uma nova oportunidade para cada animal.</p>
                        <a href="https://institutocaramelo.org" target="_blank" rel="noopener noreferrer" class="btn-ong-site">Visitar site</a>
                    </div>
                </article>

            </div>
        </section>
    </main>

    <!-- Rodapé -->
    <footer class="footer site-footer">
        <div class="site-footer-conteudo">
            <div class="site-footer-apresentacao">
                <a class="site-footer-logo" href="<?= BASE_URL ?>/index.php#inicio">Lume Pet</a>
                <p>Cuidado, carinho e bem-estar para o seu pet.</p>
            </div>

            <nav class="site-footer-coluna" aria-label="Mapa do site">
                <h2>Mapa do site</h2>
                <a href="<?= BASE_URL ?>/index.php#inicio">Início</a>
                <a href="<?= BASE_URL ?>/index.php#loja">Produtos</a>
                <a href="<?= BASE_URL ?>/index.php#adote">Adoção</a>
                <a href="<?= BASE_URL ?>/index.php#doacao">Doação</a>
                <a href="<?= BASE_URL ?>/index.php#sobre">Sobre nós</a>
                <a href="<?= BASE_URL ?>/index.php#avaliacao">Avaliações</a>
            </nav>

            <nav class="site-footer-coluna" aria-label="Institucional">
                <h2>Institucional</h2>
                <a href="<?= BASE_URL ?>/pages/ongpage.php">ONGs parceiras</a>
                <a href="<?= BASE_URL ?>/pages/politica.php">Política de privacidade</a>
            </nav>

            <div class="site-footer-coluna">
                <h2>Fale conosco</h2>
                <a href="<?= BASE_URL ?>/index.php#contato">Formulário de contato</a>
                <a href="tel:+551140028922">(11) 4002-8922</a>
                <a href="https://wa.me/5511999999999" target="_blank" rel="noopener noreferrer">WhatsApp</a>
                <a href="mailto:contato@lumepet.com.br">contato@lumepet.com.br</a>
            </div>
        </div>

        <div class="site-footer-base">
            <p>&copy; <?= date('Y') ?> Lume Pet. Todos os direitos reservados.</p>
            <a href="#topo">Voltar ao topo ↑</a>
        </div>
    </footer>

    <a href="https://wa.me/5511999999999?text=Ol%C3%A1%2C%20gostaria%20de%20mais%20informa%C3%A7%C3%B5es"
       class="whatsapp-float" target="_blank" rel="noopener noreferrer" aria-label="Conversar no WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <div class="toast-container" id="toastContainer"></div>

    <script src="<?= BASE_URL ?>/js/auth.js?v=20261018-organized-fix2"></script>
    <script src="<?= BASE_URL ?>/js/commerce.js?v=20261018-organized-fix2"></script>
    <script src="<?= BASE_URL ?>/js/site.js?v=20261018-organized-fix2"></script>
</body>
</html>