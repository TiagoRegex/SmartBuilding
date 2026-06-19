function atualizarAparelhos() {
    const divisao = document.getElementById('iot_divisao').value;
    const selectTipo = document.getElementById('iot_tipo');
    if (!selectTipo) return;
    selectTipo.innerHTML = "";
    let opcoes = [];
    if (divisao === "Sala" || divisao === "Quarto") opcoes = ["Iluminação", "Climatização", "Estores"];
    else if (divisao === "Cozinha") opcoes = ["Iluminação", "Forno", "Exaustor"];
    else if (divisao === "WC") opcoes = ["Iluminação", "Piso Radiante"];
    opcoes.forEach(function (aparelho) {
        let opt = document.createElement('option');
        opt.value = aparelho;
        opt.innerHTML = aparelho;
        selectTipo.appendChild(opt);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('iot_divisao')) atualizarAparelhos();
});