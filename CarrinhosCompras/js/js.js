const produtos = [
    { id: 1, nome: "Headset", preco: 120, img: "imagem/headset.avif" },
    { id: 2, nome: "Teclado Mecânico 75%", preco: 240, img: "imagem/teclado.avif" },
    { id: 3, nome: "Mouse Gamer Logitech sem fio", preco: 100, img: "imagem/mouse.avif" },
    { id: 4, nome: "Mousepad preto longo", preco: 25, img: "imagem/mousepad.avif" },
    { id: 5, nome: "Monitor Samsung 120Hz", preco: 900, img: "imagem/monitor.avif" },
    { id: 6, nome: "Fone Bluetooth", preco: 60, img: "imagem/fone.avif" },
    { id: 7, nome: "Caixa de Som JBL", preco: 95, img: "imagem/JBL.avif" },
    { id: 8, nome: "Iphone 17 Pro Max", preco: 10000, img: "imagem/iphone.avif" }

];

let carrinho = JSON.parse(localStorage.getItem("carrinho")) || [];

function salvar() {
    localStorage.setItem("carrinho", JSON.stringify(carrinho));
}

function listarProdutos(lista = produtos) {
    const div = document.getElementById("lista-produtos");
    div.innerHTML = "";

    lista.forEach(p => {
        const el = document.createElement("div");
        el.className = "produto";

        el.innerHTML = `
            <img src="${p.img}">
            <h3>${p.nome}</h3>
            <p>R$${p.preco}</p>
            <button onclick="adicionar(${p.id})">Adicionar</button>
        `;

        div.appendChild(el);
    });
}

function adicionar(id) {
    const item = carrinho.find(p => p.id === id);

    if (item) {
        item.qtd++;
    } else {
        const produto = produtos.find(p => p.id === id);
        carrinho.push({ ...produto, qtd: 1 });
    }

    atualizar();
}

function remover(id) {
    const item = carrinho.find(p => p.id === id);

    if (item.qtd > 1) {
        item.qtd--;
    } else {
        carrinho = carrinho.filter(p => p.id !== id);
    }

    atualizar();
}

function listarCarrinho() {
    const div = document.getElementById("lista-carrinho");
    div.innerHTML = "";

    carrinho.forEach(p => {
        const el = document.createElement("div");

        el.innerHTML = `
            <p>${p.nome} (x${p.qtd}) - R$${p.qtd * p.preco}</p>
            <button onclick="remover(${p.id})">Remover</button>
        `;

        div.appendChild(el);
    });
}

function total() {
    const soma = carrinho.reduce((acc, p) => acc + p.preco * p.qtd, 0);
    document.getElementById("total").innerText = `Total: R$${soma.toFixed(2)}`;
}

function atualizar() {
    listarCarrinho();
    total();
    salvar();
}

document.getElementById("filtro").addEventListener("change", (e) => {
    let valor = e.target.value;

    if (valor === "ate50") {
        listarProdutos(produtos.filter(p => p.preco <= 50));
    } else if (valor === "acima50") {
        listarProdutos(produtos.filter(p => p.preco > 50));
    } else {
        listarProdutos();
    }
});

document.getElementById("limpar").addEventListener("click", () => {
    carrinho = [];
    atualizar();
});

listarProdutos();
atualizar();