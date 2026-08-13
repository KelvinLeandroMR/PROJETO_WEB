/**
 * Camada de comunicação com a API (fetch + sessão via cookie).
 */
const Api = {
    async _request(metodo, caminho, corpo) {
        const opcoes = {
            method: metodo,
            credentials: 'include', // envia/recebe o cookie de sessão PHP
            headers: {},
        };
        if (corpo !== undefined) {
            opcoes.headers['Content-Type'] = 'application/json';
            opcoes.body = JSON.stringify(corpo);
        }

        let resposta;
        try {
            resposta = await fetch(`${API_BASE_URL}/${caminho}`, opcoes);
        } catch (erroRede) {
            throw new Error('Não foi possível conectar ao servidor. Verifique se o backend PHP está em execução.');
        }

        let json;
        try {
            json = await resposta.json();
        } catch (erroParse) {
            throw new Error('Resposta inesperada do servidor.');
        }

        if (resposta.status === 401) {
            // Sessão expirada/ausente: manda de volta para o login
            if (!location.pathname.endsWith('login.html')) {
                sessionStorage.setItem('crud_mundo_redirect_msg', json.mensagem || 'Sessão expirada. Faça login novamente.');
                location.href = 'login.html';
            }
            throw new Error(json.mensagem || 'Não autenticado.');
        }

        if (!json.sucesso) {
            const erro = new Error(json.mensagem || 'Ocorreu um erro.');
            erro.dados = json.dados;
            erro.status = resposta.status;
            throw erro;
        }

        return json.dados;
    },

    get(caminho) { return this._request('GET', caminho); },
    post(caminho, corpo) { return this._request('POST', caminho, corpo); },
    put(caminho, corpo) { return this._request('PUT', caminho, corpo); },
    delete(caminho) { return this._request('DELETE', caminho); },
};
