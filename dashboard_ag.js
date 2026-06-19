function atualizarExemplo() {
        const tipo = document.getElementById('tipo_user').value;
        const inputId = document.getElementById('novo_id');
        if(tipo === 'CLT') inputId.placeholder = "Ex: E1A1CLT1";
        if(tipo === 'AL') inputId.placeholder = "Ex: E1AL1";
        if(tipo === 'AG') inputId.placeholder = "Ex: AG2";
    }