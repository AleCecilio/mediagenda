function mostrarSobre(){
    Swal.fire({
        title: 'MediAgenda NodeJS',
        html: '<strong>Primeiro app em NodeJS</strong><br><br>' +
              'Backend: NodeJS + Express<br>' + 
              'Frontend: Bootstrap<br>' +
              'Comunicação via API REST',
        icon: 'info',
        confirmButtonText: 'Fechar'
    });
}
function carregarStatus(){
    fetch('/api/status')
        .then(function(resposta){
            return resposta.json();
        })
        .then(function(dados){
            var statusSistema = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> ' +
                                dados.status.toUpperCase() +
                                '</span> ' + 
                                dados.mensagem; //ajeitar aqui!!!
            document
              .getElementById('statusSistema')
              .innerHTML = statusSistema;
        })
        .catch(function(){
            /*document
              .getElementById('statusSistema')
              .innerText = 'Erro ao carregar';*/
            Swal.fire({
                icon: 'error',
                title:'Erro',
                text: 'Erro ao carregar status.'
            });
        });
}
function carregarConsultas(){
    fetch('/api/consultas')
        .then(function(resposta){
            return resposta.json();
        })
        .then(function(consultas){
            //trago a tabela do html para uma variável em JS
            var tabela = document.getElementById('tabelaConsultas');
            tabela.innerHTML = '';            
            consultas.forEach(function(consulta){
                var classStatus = consulta.status.toUpperCase() === 'CONFIRMADO'
                    ? 'success'
                    : 'warning';
                //fazendo debug no console do navegador:
                console.log('>>> ' + consulta.status);
                console.log('>>> ' + consulta.status.toUpperCase());
                var linha = '<tr>' +
                    '<td>' + consulta.paciente      + '</td>' +
                    '<td>' + consulta.medico        + '</td>' +
                    '<td>' + consulta.especialidade + '</td>' +
                    '<td>' + consulta.data          + '</td>' +
                    '<td>' + consulta.horario       + '</td>' +
                    '<td> <span class="badge bg-' + classStatus + '">' 
                           + consulta.status        + '</span></td>' +
                    '</tr>';
                tabela.innerHTML += linha;
            });
            Swal.fire({
                toast: true,
                position:'top-end',
                icon:'success',
                title:'Consultas carregadas',
                showConfirmButton: false,
                timer: 2800
            });
        })
        .catch(function(){
            //alert('Erro ao carregar consultas.');
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro ao carregar consultas'
            });
        });
}
carregarStatus();
carregarConsultas();

