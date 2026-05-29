var modalAgendamento;

document.addEventListener('DOMContentLoaded', function() {
    modalAgendamento = new bootstrap.Modal(
        document.getElementById('modalAgendamento')
    );

    pesquisarAgendamentos();
});

function pesquisarAgendamentos() {

    var url = '/api/agendamentos/pesquisar/filtros?';

    var paciente = document.getElementById('filtroPaciente').value;
    var medico = document.getElementById('filtroMedico').value;
    var especialidade = document.getElementById('filtroEspecialidade').value;
    var dataInicio = document.getElementById('filtroDataInicio').value;
    var dataFim = document.getElementById('filtroDataFim').value;

    var filtros = [];

    if (paciente) {
        filtros.push('paciente=' + encodeURIComponent(paciente));
    }

    if (medico) {
        filtros.push('medico_id=' + encodeURIComponent(medico));
    }

    if (especialidade) {
        filtros.push('especialidade_id=' + encodeURIComponent(especialidade));
    }

    if (dataInicio) {
        filtros.push('data_inicio=' + encodeURIComponent(dataInicio));
    }

    if (dataFim) {
        filtros.push('data_fim=' + encodeURIComponent(dataFim));
    }

    url += filtros.join('&');
    console.log('URL de pesquisa: ' + url);
    fetch(url)
        .then(function(response) {
            console.log('Resposta da pesquisa: ' + response.status);
            return response.json();
        })
        .then(function(lista) {
            console.log('Agendamentos encontrados: ' + lista.length);
            montarTabela(lista);
        })
        .catch(function() {
            Swal.fire('Erro', 'Erro ao buscar agendamentos.', 'error');
        });
}

function montarTabela(lista) {

    var tabela = document.getElementById('tabelaConsultas');
    tabela.innerHTML = '';

    if (lista.length === 0) {
        tabela.innerHTML =
            '<tr>' +
                '<td colspan="8" class="text-center text-muted">' +
                    'Nenhum agendamento encontrado.' +
                '</td>' +
            '</tr>';
        return;
    }

    lista.forEach(function(item) {

        var corStatus = 'secondary';

        if (item.status === 'Confirmado') {
            corStatus = 'success';
        } else if (item.status === 'Pendente') {
            corStatus = 'warning';
        } else if (item.status === 'Cancelado') {
            corStatus = 'danger';
        }

        tabela.innerHTML +=
            '<tr>' +
                '<td>' + item.id + '</td>' +
                '<td>' + item.paciente + '</td>' +
                '<td>' + item.medico + '</td>' +
                '<td>' + item.especialidade + '</td>' +
                '<td>' + formatarDataBR(item.data) + '</td>' +
                '<td>' + item.horario + '</td>' +
                '<td><span class="badge bg-' + corStatus + '">' + item.status + '</span></td>' +
                '<td>' +
                    '<button class="btn btn-sm btn-warning me-1" onclick="editarAgendamento(' + item.id + ')">' +
                        '<i class="fa-solid fa-pen"></i>' +
                    '</button>' +
                    '<button class="btn btn-sm btn-danger" onclick="deletarAgendamento(' + item.id + ')">' +
                        '<i class="fa-solid fa-trash"></i>' +
                    '</button>' +
                '</td>' +
            '</tr>';
    });
}

function abrirNovoAgendamento() {

    document.getElementById('tituloModal').innerText = 'Novo Agendamento';
    document.getElementById('agendamentoId').value = '';
    document.getElementById('paciente').value = '';
    document.getElementById('medico_id').value = '';
    document.getElementById('especialidade_id').value = '';
    document.getElementById('data').value = '';
    document.getElementById('horario').value = '';
    document.getElementById('status').value = 'Confirmado';

    modalAgendamento.show();
}

function editarAgendamento(id) {

    fetch('/api/agendamentos/' + id)
        .then(function(response) {
            return response.json();
        })
        .then(function(item) {

            document.getElementById('tituloModal').innerText = 'Editar Agendamento';
            document.getElementById('agendamentoId').value = item.id;
            document.getElementById('paciente').value = item.paciente;
            document.getElementById('data').value = formatarData(item.data);
            document.getElementById('horario').value = item.horario;
            document.getElementById('status').value = item.status;

            preencherSelectPorTexto('medico_id', item.medico);
            preencherSelectPorTexto('especialidade_id', item.especialidade);

            modalAgendamento.show();
        })
        .catch(function() {
            Swal.fire('Erro', 'Erro ao carregar agendamento.', 'error');
        });
}

function preencherSelectPorTexto(idSelect, texto) {

    var select = document.getElementById(idSelect);

    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].text === texto) {
            select.selectedIndex = i;
            return;
        }
    }
}

function salvarAgendamento() {

    var id = document.getElementById('agendamentoId').value;

    var agendamento = {
        paciente: document.getElementById('paciente').value,
        medico_id: document.getElementById('medico_id').value,
        especialidade_id: document.getElementById('especialidade_id').value,
        data: document.getElementById('data').value,
        horario: document.getElementById('horario').value,
        status: document.getElementById('status').value
    };

    var url = '/api/agendamentos';
    var metodo = 'POST';

    if (id) {
        url = '/api/agendamentos/' + id;
        metodo = 'PUT';
    }

    fetch(url, {
        method: metodo,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(agendamento)
    })
    .then(function(response) {
        return response.json();
    })
    .then(function(data) {

        if (data.erro) {
            Swal.fire('Atenção', data.mensagem, 'warning');
            return;
        }

        modalAgendamento.hide();

        Swal.fire({
            icon: 'success',
            title: 'Sucesso',
            text: data.mensagem,
            timer: 1800,
            showConfirmButton: false
        });

        pesquisarAgendamentos();
    })
    .catch(function() {
        Swal.fire('Erro', 'Erro ao salvar agendamento.', 'error');
    });
}

function deletarAgendamento(id) {

    Swal.fire({
        title: 'Deseja excluir?',
        text: 'Essa ação removerá o agendamento.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then(function(result) {

        if (result.isConfirmed) {

            fetch('/api/agendamentos/' + id, {
                method: 'DELETE'
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {

                if (data.erro) {
                    Swal.fire('Atenção', data.mensagem, 'warning');
                    return;
                }

                Swal.fire({
                    icon: 'success',
                    title: 'Excluído',
                    text: data.mensagem,
                    timer: 1800,
                    showConfirmButton: false
                });

                pesquisarAgendamentos();
            })
            .catch(function() {
                Swal.fire('Erro', 'Erro ao excluir agendamento.', 'error');
            });
        }
    });
}

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

function limparFiltros() {
    document.getElementById('filtroPaciente').value = '';
    document.getElementById('filtroMedico').value = '';
    document.getElementById('filtroEspecialidade').value = '';
    document.getElementById('filtroDataInicio').value = '';
    document.getElementById('filtroDataFim').value = '';

    pesquisarAgendamentos();
}

function formatarData(dataBanco) {
    if (!dataBanco) {
        return '';
    }

    return dataBanco.substring(0, 10);
}

function formatarDataBR(dataBanco) {
    if (!dataBanco) {
        return '';
    }

    var data = dataBanco.substring(0, 10);
    var partes = data.split('-');

    return partes[2] + '/' + partes[1] + '/' + partes[0];
}