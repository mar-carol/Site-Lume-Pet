<!-- sobrepontos.php é a página escrita sobre como vai funcionar o programa de pontos da LP -->


<?php
require_once __DIR__ . '/../config/database.php';

$tituloPagina = 'Sobre os Pontos | Lume Pet';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPagina) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet"
          href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>

<body class="pagina-sobre-pontos">
    <header class="sp-header">
        <div class="sp-header-conteudo">
            <?php require __DIR__ . '/../includes/user_home_logo.php'; ?>
            <span class="sp-header-titulo">Programa de pontos</span>
            <a class="sp-header-voltar" href="<?= BASE_URL ?>/index.php#loja">Voltar à loja</a>
        </div>
    </header>

    <main class="sp-container">

        <section class="sp-hero">
            <div class="sp-hero-texto">
                <span class="sp-etiqueta">
                    Programa de pontos Lume Pet
                </span>

                <h1>
                    Seu carinho pelos pets
                    <span>também vale pontos.</span>
                </h1>

                <p>
                    Saiba como acompanhar os pontos recebidos pelas suas
                    doações e consulte seu saldo pela sua conta.
                </p>

                <a class="sp-botao sp-botao-principal"
                   href="<?= BASE_URL ?>/usuario/pontos.php">
                    Ver meus pontos
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="sp-hero-ilustracao" aria-hidden="true">
                <div class="sp-pontos-destaque">
                    <span class="sp-pontos-icone">✦</span>
                    <strong>1 ponto</strong>
                    <span>a cada real inteiro doado</span>
                </div>
            </div>
        </section>

        <section class="sp-introducao">
            <span class="sp-sobretitulo">SIMPLES DE ACOMPANHAR</span>

            <h2>O que são os pontos Lume Pet?</h2>

            <p>
                Os pontos são creditados de acordo com o valor das suas
                doações. Acompanhe o saldo e o histórico na sua área de
                pontos sempre que quiser.
            </p>
        </section>

        <section class="sp-beneficios">
            <article class="sp-card">
                <div class="sp-card-icone sp-icone-azul"
                     aria-hidden="true">🛍️</div>

                <h3>Participe</h3>

                <p>
                    Faça uma doação para apoiar uma ONG parceira e
                    contribuir com o cuidado dos animais.
                </p>
            </article>

            <article class="sp-card">
                <div class="sp-card-icone sp-icone-laranja"
                     aria-hidden="true">⭐</div>

                <h3>Acumule</h3>

                <p>
                    Você recebe 1 ponto por cada real inteiro doado.
                    O crédito é registrado na sua conta.
                </p>
            </article>

            <article class="sp-card">
                <div class="sp-card-icone sp-icone-verde"
                     aria-hidden="true">🐾</div>

                <h3>Acompanhe</h3>

                <p>
                    Veja o saldo atualizado e consulte os registros
                    de pontos na sua conta.
                </p>
            </article>
        </section>

        <section class="sp-como-funciona">
            <div class="sp-como-texto">
                <span class="sp-sobretitulo">É SIMPLES</span>

                <h2>Como funciona?</h2>

                <p>
                    Os pontos são calculados a partir das doações e
                    ficam disponíveis para consulta na sua conta.
                </p>
            </div>

            <ol class="sp-etapas">
                <li>
                    <span class="sp-numero">01</span>
                    <div>
                        <h3>Entre na sua conta</h3>
                        <p>
                            Entre na sua conta para acessar seu saldo
                            e o histórico de pontos.
                        </p>
                    </div>
                </li>

                <li>
                    <span class="sp-numero">02</span>
                    <div>
                        <h3>Confira seus pontos</h3>
                        <p>
                            Confira os pontos recebidos pelas suas
                            doações.
                        </p>
                    </div>
                </li>

                <li>
                    <span class="sp-numero">03</span>
                    <div>
                        <h3>Aproveite os benefícios</h3>
                        <p>
                            Consulte o histórico para acompanhar
                            quando os pontos foram creditados.
                        </p>
                    </div>
                </li>
            </ol>
        </section>

        <section class="sp-aviso">
            <span class="sp-aviso-icone" aria-hidden="true">ℹ</span>

            <div>
                <h3>Importante saber</h3>

                <p>
                    Os pontos são calculados com base no valor inteiro
                    doado. Consulte seu histórico para acompanhar os
                    créditos registrados na sua conta.
                </p>
            </div>
        </section>

        <section class="sp-cta">
            <span aria-hidden="true">🐶 🐱</span>

            <h2>Faça parte do universo Lume Pet!</h2>

            <p>
                Acesse sua conta e acompanhe as novidades do programa.
            </p>

            <a class="sp-botao sp-botao-claro"
               href="<?= BASE_URL ?>/usuario/pontos.php">
                Consultar meus pontos
                <span aria-hidden="true">→</span>
            </a>
        </section>

    </main>

</body>
</html>