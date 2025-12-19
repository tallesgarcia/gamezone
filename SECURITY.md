🔐 Política de Segurança – Game Zone
📌 Visão Geral

O Game Zone é uma plataforma web gamificada desenvolvida como Projeto de Conclusão de Curso (TCC) no curso Técnico em Desenvolvimento de Sistemas.
Esta política de segurança descreve as práticas adotadas para proteger os dados dos usuários, garantir a integridade do sistema e orientar o tratamento de possíveis vulnerabilidades.

🛡️ Princípios de Segurança

O projeto segue os seguintes princípios fundamentais:

Proteção dos dados pessoais dos usuários

Minimização de riscos de acesso não autorizado

Validação e tratamento adequado das entradas de dados

Separação entre lógica da aplicação e arquivos sensíveis

Conformidade básica com a Lei Geral de Proteção de Dados (LGPD)

🔑 Autenticação e Controle de Acesso

O sistema possui autenticação de usuários por meio de login e senha

Senhas são armazenadas de forma segura utilizando técnicas de hash

Sessões são utilizadas para controle de acesso às funcionalidades restritas

Funcionalidades administrativas são protegidas contra acesso não autorizado

🧾 Proteção de Dados Pessoais (LGPD)

O projeto adota boas práticas alinhadas à LGPD:

Coleta apenas de dados necessários para o funcionamento do sistema

Uso dos dados exclusivamente para fins da aplicação

Não compartilhamento de dados com terceiros

Estrutura preparada para futuras melhorias em transparência e consentimento

🧼 Validação e Sanitização de Dados

Entradas de usuários são validadas no back-end

Dados recebidos via formulários passam por sanitização

Proteção contra ataques comuns, como:

SQL Injection

Cross-Site Scripting (XSS)

Uso de bibliotecas auxiliares quando necessário para reforçar a segurança

🗄️ Banco de Dados

A conexão com o banco de dados é mantida em arquivo separado

Arquivos de configuração sensíveis não devem ser versionados

Uso de consultas preparadas sempre que possível

Estrutura preparada para controle de permissões por usuário

📂 Organização e Versionamento

Arquivos sensíveis (ex.: conexão com banco) são excluídos do versionamento via .gitignore

O repositório público contém apenas código necessário para avaliação e estudo

Commits seguem uma descrição clara para facilitar auditoria e manutenção

🚨 Relato de Vulnerabilidades

Caso seja identificada alguma falha de segurança:

Evite divulgar a vulnerabilidade publicamente

Entre em contato diretamente com o autor do projeto

A falha será analisada e corrigida conforme a prioridade

📧 Contato: tallesgarcia2018@gmail.com

📌 Limitações

Por se tratar de um projeto acadêmico e em desenvolvimento:

Algumas medidas avançadas de segurança ainda não estão implementadas

O sistema não deve ser utilizado em ambiente de produção real

Melhorias contínuas estão previstas conforme a evolução do projeto

👤 Responsável

Talles Costa Garcia
Desenvolvedor Júnior
Técnico em Desenvolvimento de Sistemas

📄 Observação Final

Esta política de segurança tem caráter educacional e demonstrativo, refletindo o comprometimento do projeto com boas práticas de desenvolvimento seguro e proteção de dados.
