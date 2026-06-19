// Mostra alertas de sucesso/erro com base em parâmetros na URL,
// em vez de o PHP gerar blocos <script> diretamente no HTML.
document.addEventListener('DOMContentLoaded', function () {
    const params = new URLSearchParams(window.location.search);

    if (params.get('erro_login') === '1') {
        alert('Erro: ID de Utilizador ou Password incorretos!');
    }

    if (params.get('pass_alterada') === '1') {
        alert('Password alterada com sucesso!');
    }

    if (params.get('erro_pass') === '1') {
        alert('Erro: A password atual está incorreta!');
    }
});