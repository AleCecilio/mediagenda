var mysql = require('mysql2');

var conexao = mysql.createConnection({
    host: 'mysql',
    port: 3306,
    user: 'root',
    password: 'root123',
    database: 'mediagenda'
});

conexao.connect(function(erro){
    if(erro){
        console.log('Erro ao conectar no banco de dados:');
        console.log(erro);
        return;
    }
    console.log('Conectado ao MySQL com sucesso!');
});
module.exports = conexao;