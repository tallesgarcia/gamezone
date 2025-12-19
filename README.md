# 🎮 Game Zone



O Game Zone é uma plataforma web gamificada voltada ao público gamer, desenvolvida como Projeto de Conclusão de Curso (TCC) no curso Técnico em Desenvolvimento de Sistemas.



O sistema tem como objetivo criar um ambiente interativo e seguro para gamers, permitindo a criação de comunidades, interação entre usuários e aplicação de conceitos de gamificação.



---



# 🧠 Objetivo do Projeto



- Criar uma plataforma exclusiva para gamers

- Promover interação social por meio de comunidades e fóruns

- Aplicar conceitos de desenvolvimento web aprendidos no curso técnico

- Desenvolver um sistema funcional utilizando boas práticas de programação



---



# 🛠️ Tecnologias Utilizadas



- PHP

- JavaScript

- MySQL

- HTML5

- CSS3

- Tailwind CSS

- Arquitetura baseada em separação de responsabilidades



---



# 📂 Estrutura do Projeto



A estrutura do projeto foi organizada de forma simples e funcional, adequada a um projeto acadêmico e em evolução:



gamezone/

├── .vs/

│   └── gamezone/

│       ├── copilot-chat/

│       ├── CopilotIndices/

│       ├── FileContentIndex/

│       ├── v17/

│       ├── ProjectSettings.json

│       ├── slnx.sqlite

│       └── VSWorkspaceState.json

├── actions/

│   └── responder\_pedido.php

├── admin/

│   ├── acoes/

│   │   ├── ativar\_produto.php

│   │   ├── atualizar\_status\_denuncia.php

│   │   ├── buscar\_notificacoes.php

│   │   ├── desativar\_produto.php

│   │   ├── desmarcar\_popular\_comunidade.php

│   │   ├── equipe\_excluir.php

│   │   ├── excluir\_avaliacao.php

│   │   ├── excluir\_comunidade.php

│   │   ├── excluir\_produto.php

│   │   ├── excluir\_usuario.php

│   │   ├── jogos\_excluir.php

│   │   ├── marcar\_notificacoes\_lidas.php

│   │   ├── marcar\_popular\_comunidade.php

│   │   ├── promover\_usuario.php

│   │   └── rebaixar\_usuario.php

│   └── exportacoes/

│       ├── exportar\_compras\_csv.php

│       ├── exportar\_compras\_pdf.php

│       └── exportar\_compras\_xlsx.php

│   ├── admin\_avaliacoes.php

│   ├── admin\_compras.php

│   ├── admin\_comunidades.php

│   ├── admin\_configuracoes.php

│   ├── admin\_denuncias.php

│   ├── admin\_equipe.php

│   ├── admin\_jogos.php

│   ├── admin\_noticias.php

│   ├── admin\_painel.php

│   ├── admin\_produtos.php

│   ├── admin\_usuarios.php

│   ├── buscar\_compras.php

│   ├── buscar\_notificacoes.php

│   ├── equipe\_adicionar.php

│   ├── equipe\_editar.php

│   ├── jogos\_adicionar.php

│   ├── jogos\_editar.php

│   ├── marcar\_notificacoes\_lidas.php

│   ├── produtos\_adicionar.php

│   ├── produtos\_editar.php

├── assets/

│   ├── css/

│   │   └── estilos.css

│   └── img/

│       ├── capacyber.jpeg

│       ├── capas/

│       ├── cyberpunk/

│       │   └── Capa/

│       ├── galeria/

│       ├── gamezone-logo.png

│       └── team.png

├── config/

│   └── db.php

├── conta/

│   ├── buscar\_notificacoes.php

│   ├── concluir\_missao.php

│   ├── configuracoes.php

│   ├── marcar\_notificacoes\_lidas.php

│   ├── missoes.php

│   └── perfil.php

├── includes/

│   ├── amizades\_pendentes\_count.php

│   ├── getImagemProduto.php

│   ├── sidebar.php

│   └── verificar\_manutencao.php

├── pages/

│   └── comunidade/

│       ├── amigos.php

│       ├── amigos\_pendentes.php

│       ├── bloqueados.php

│       ├── chat.php

│       ├── conversas.php

│       ├── criar\_canal.php

│       ├── criar\_comunidade.php

│       ├── criar\_forum.php

│       ├── forum.php

│       ├── forum\_ver.php

│       ├── explorar\_comunidades.php

│       ├── minhas\_comunidades.php

│       ├── ver\_comunidade.php

│       ├── online.php

│       └── dezenas de endpoints AJAX (get\_\*, enviar\_\*, limpar\_\*)

│   └── reportar/

│       ├── denunciar\_usuario.php

│       ├── buscar\_amigos.php

│       ├── buscar\_notificacoes.php

│       ├── marcar\_notificacoes\_lidas.php

│       └── uploads/

│           └── denuncias/

│   └── security/

│       ├── cadastrar.html

│       ├── cadastrar.php

│       ├── entrar.php

│       └── forgot\_password.php

├── vendor/

│   ├── dompdf/

│   ├── phenx/

│   ├── sabberworm/

│   ├── setasign/

│   └── symfony/

│   ├── criar\_canal.php

│   ├── marcar\_notificacoes\_lidas.php

│   ├── minhas\_comunidades.php

│   ├── participar\_servidor.php

│   ├── sair\_servidor.php

│

├── aceitar\_pedido.php

├── add\_amigo.php

├── avaliar\_plataforma.php

├── buscar\_notificacoes.php

├── composer.json

├── composer.lock

├── comprar.php

├── contato.php

├── enviar\_pedido\_amizade.php

├── equipe.php

├── finalizar\_compra.php

├── historico\_compras.php

├── index.php

├── loja.php

├── marcar\_notificacoes\_lidas.php

├── meus\_podutos.php

├── noticia.php

├── noticias.php

├── pagamento.php

├── pagamento\_sucesso.php

│

└── README.md # Documentação do projeto



> A organização prioriza clareza, fácil manutenção e entendimento do código, sendo adequada ao escopo atual do projeto.



---



# 🧩 Funcionalidades Implementadas



- Cadastro e login de usuários

- Sistema de autenticação

- Estrutura para comunidades gamers

- Interação entre usuários

- Base para fóruns e chats

- Interface responsiva

- Preparação para aplicação de gamificação



---



# 🔐 Segurança e Boas Práticas



- Separação de arquivos de configuração

- Uso de validações no back-end

- Estrutura preparada para melhorias em segurança

- Atenção à conformidade com a LGPD



---



# 📌 Status do Projeto



🚧 Em desenvolvimento



O projeto segue em constante evolução, com melhorias planejadas tanto em funcionalidades quanto em organização de código.



---



# 👤 Autor



Talles Costa Garcia  

Desenvolvedor Júnior  

Técnico em Desenvolvimento de Sistemas  



---



# 📄 Observação



Este projeto foi desenvolvido com fins acadêmicos, servindo também como portfólio para apresentação de habilidades técnicas em desenvolvimento web.



