# AluguelPRO: o que falta ajustar

Documento de passagem de bastão. Descreve o sistema, o que já foi corrigido e **tudo o que ainda precisa ser feito**, em ordem de prioridade. Cada item traz arquivo, problema e a correção esperada.

> Última atualização: 26/09/2026 — P1 concluído e em produção (deploy na VPS LS.CONNECT)

---

## 1. Contexto do sistema

Sistema web de controle de aluguéis: inquilinos, proprietários, imóveis, contratos, parcelas (contas a receber), recibos, manutenções, relatórios e usuários.

**Stack**
- PHP procedural, sem framework e sem Composer. Roda em **PHP 8.3** (Apache `mod_php`).
- MySQL 8 via `mysqli`, com SQL montado em string e escapado por `mysqli_real_escape_string`.
- Front-end: Bootstrap 5.3, Bootstrap Icons, jQuery 3.7, DataTables 1.13, Chart.js 4, todos via CDN.

**Estrutura**
```
config/config.php               constantes do sistema, sessão, timezone (America/Manaus)
config/config.local.php         credenciais do banco (NÃO versionado; copiar do .example)
config/db.php                   helpers: db_connect, db_query, db_fetch_all, db_fetch_one, db_insert
includes/functions.php          sessão/permissão, formatação, numeração, gerar_parcelas,
                                transações (db_transacao/db_exec), sincronizar_status_imovel,
                                calcular_encargos, badges
includes/header|navbar|footer   layout (sidebar + topbar)
pages/*.php                     telas: HTML + modal + JS embutido na string PHP $extra_js
ajax/*.php                      endpoints JSON: switch($action) list/get/create/update/delete
dashboard.php                   indicadores + gráfico de receita
banco.sql                       schema completo + admin padrão + configurações
migracoes/                      scripts SQL para bancos já existentes
```

**Fluxo principal:** criar contrato → gera uma parcela por mês entre início e fim → marca o imóvel como `alugado`. Pagar parcela → status `pago` + gera recibo (`REC-AAAA-NNNNN`). Parcelas vencidas passam de `pendente` para `atrasado` via `atualizar_parcelas_atrasadas()`.

**Convenções já adotadas (seguir nas próximas mudanças)**
- Operações com várias escritas usam `db_transacao($conn, fn)` e `db_exec()` (que lança exceção).
- Erro de negócio: `throw new RegraNegocioException('mensagem para o usuário')`; o endpoint captura com `catch (Throwable $e) { json_erro($e); }`. Erros técnicos vão para o log, nunca para a tela.
- Datas: validar com `data_valida()` (formato `Y-m-d`).
- Status do imóvel: nunca alterar direto; chamar `sincronizar_status_imovel($conn, $imovel_id)`.
- **Atenção ao JS das páginas:** ele fica dentro de uma string PHP com aspas simples (`$extra_js = '<script>...'`). Aspas simples no JS precisam ser `\'`, e `<?= ?>` **não funciona** ali dentro (use concatenação `' . $var . '`).

### Como rodar localmente
1. Importar `banco.sql` no MySQL (cria `aluguel_db`).
2. Copiar `config/config.local.example.php` para `config/config.local.php` e preencher as credenciais.
3. Servir a pasta em `/aluguel` (constante `BASE_URL` em `config/config.php`).
4. Login padrão: `admin@sistema.com` / `admin123` (**trocar**).

Não há testes automatizados. As correções da seção 2 foram testadas manualmente, chamando os endpoints via CLI contra um banco de teste.

---

## 2. Já corrigido (regras de negócio)

- [x] Editar contrato duplicava parcelas pagas ou atrasadas. `gerar_parcelas` agora preserva pagas e canceladas, só recria as abertas sem recibo, e só é chamado quando datas, valor ou dia de vencimento mudam.
- [x] Pagamento duplicado da mesma parcela. Corrigido com transação + `SELECT ... FOR UPDATE` + checagem de status e recibo; `UNIQUE(parcela_id)` em `recibos`.
- [x] Numeração de contrato e recibo por `COUNT(*)` repetia números após exclusões. Agora usa `MAX` do sequencial do ano (`proximo_numero()`).
- [x] Excluir contrato liberava o imóvel mesmo quando a exclusão falhava. Agora contrato com pagamentos não pode ser excluído (orienta a encerrar).
- [x] Status do imóvel desincronizado ao trocar o imóvel ou o status do contrato.
- [x] `atrasado` só era atualizado no dashboard; agora também em contas e relatórios.
- [x] Pagamento: rótulo "Aluguel Pago", total ao vivo no modal, multa e juros sugeridos a partir de `configuracoes`, recibo usa o valor realmente pago.
- [x] Validações de contrato (datas, valor, dia 1–31, um contrato ativo por imóvel) e de pagamento (data não futura, forma válida).
- [x] JS da tela Contas a Receber estava quebrado (`<?= ?>` dentro da string `$extra_js`).
- [x] Badge de prioridade, texto "Térmito", total do relatório de receita, `CONCAT` com número nulo, exclusão de imóvel com contratos encerrados ou manutenções.
- [x] Credenciais do banco movidas para `config/config.local.php` (fora do git).
- [x] Tela de login não vem mais preenchida com as credenciais do admin.

---

## 3. Pendências

### 🔴 P1: Segurança (fazer primeiro)

- [x] **1. Senha padrão do admin.** ✅ Troca obrigatória no 1º acesso (`trocar_senha.php`, flag `usuarios.trocar_senha`). Usuários criados/resetados pelo admin também trocam no 1º acesso. O login pré-preenchido já foi removido de `login.php`, mas a senha `admin123` (documentada no `banco.sql`) ainda precisa ser trocada em produção pela tela Usuários. Considerar forçar a troca de senha no primeiro acesso.

- [x] **2. Senhas em MD5 sem salt.** ✅ `password_hash`/`password_verify`, upgrade transparente do MD5 no login, migração 002.
  `ajax/auth.php` (login) e `ajax/usuarios.php` (create/update) usam `md5()`. Migrar para `password_hash()` / `password_verify()`.
  - A coluna `usuarios.senha` é `VARCHAR(64)`: aumentar para `VARCHAR(255)` (criar `migracoes/002_...sql` e atualizar `banco.sql`).
  - Migração transparente: no login, se o hash armazenado tiver 32 caracteres hex (MD5) e `md5($senha)` bater, regravar com `password_hash`.
  - Buscar o usuário só pelo e-mail e verificar a senha em PHP (não comparar a senha no SQL).
  - Ajustar o INSERT do admin em `banco.sql` (gerar hash com `password_hash`).

- [x] **3. XSS armazenado nas telas.** ✅ `esc()` global em `app.js`; DataTables escapa toda coluna sem render (columnDefs); nomes fora do `onclick` (data-nome); `<option>`/`pag_info` escapados; toast via `textContent`. Testado com payload `<img onerror>`: exibido como texto. Dados do banco são inseridos como HTML sem escape. Criar um helper JS global em `assets/js/app.js`:
  ```js
  function esc(s) { return String(s ?? "").replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c])); }
  ```
  (dentro de `$extra_js` as aspas simples precisam de escape; melhor declarar em `app.js`). Pontos a corrigir:
  - **Colunas DataTables sem `render`** (DataTables insere como HTML), por exemplo `{ data: "nome", render: d => esc(d) }`, em:
    `pages/inquilinos.php:155-158`, `pages/proprietarios.php:146-149`, `pages/imoveis.php:174` (e demais colunas de texto da tela), `pages/contratos.php:136-138`, `pages/contas.php:157-158`, `pages/recibos.php:34-36`, `pages/manutencoes.php:144-145`, `pages/usuarios.php:91-92`.
  - **Botões com `onclick` montado com o nome**: `pages/inquilinos.php:162` (`row.nome.replace(...)` dentro de `onclick`) e padrões iguais nas outras telas. Passar só o `id` e buscar o nome em JS, ou usar `data-*` + listener.
  - **`innerHTML` com dados**: `pages/contratos.php:120,125`, `pages/imoveis.php:157`, `pages/manutencoes.php:128` (montagem de `<option>`; usar `new Option(texto, valor)`), `pages/contas.php:188` (`pag_info`).
  - **`showToast()` em `assets/js/app.js`** insere `message` como HTML: usar `textContent` no `.toast-body`.
  - Revisar `dashboard.php` e `pages/relatorios.php` por `<?= $var ?>` sem `htmlspecialchars` (os relatórios em `ajax/relatorios.php` já escapam a maior parte).

- [x] **4. Sem proteção CSRF.** ✅ Token por sessão; validado AUTOMATICAMENTE em todo POST de `ajax/*` (bloco no fim de `functions.php`); enviado automaticamente pelo `fetch`/jQuery (header `X-CSRF-Token`). Login/troca de senha enviam via meta tag. Nenhum POST valida token.
  - Gerar `$_SESSION['csrf']` (`bin2hex(random_bytes(32))`) no login e expor em `navbar.php` (`window.CSRF = '...'`).
  - Enviar o token em `ajaxPost()` (`assets/js/app.js`) e nos forms da tela de login.
  - Validar em todos os `ajax/*.php` para ações que alteram dados (helper `require_csrf()` em `functions.php`, com `hash_equals`).

- [x] **5. Sessão.** ✅ `session_regenerate_id`, SameSite=Lax, Secure atrás do proxy HTTPS, limite de 5 falhas/e-mail ou 20/IP em 15 min (tabela `login_tentativas`). Timeout de inatividade: não feito (opcional).
  - `ajax/auth.php`: chamar `session_regenerate_id(true)` após o login bem-sucedido.
  - `config/config.php`: `session.cookie_samesite=Lax` e `session.cookie_secure=1` quando em HTTPS.
  - Limitar tentativas de login (contador por e-mail/IP na sessão ou numa tabela, com bloqueio temporário).
  - Timeout de inatividade (opcional).

- [~] **6. Autorização.** ✅ Parcial: exclusões só para admin (automático em `functions.php`), menu Usuários só para admin. PENDENTE decisão do dono: operador pode cancelar parcelas / editar contratos encerrados? Só `ajax/usuarios.php` e `pages/usuarios.php` verificam `is_admin()`. Definir o que o nível `operador` pode fazer (sugestão: sem exclusões, sem cancelar parcelas, sem editar contratos encerrados) e aplicar nos endpoints. Esconder o menu "Usuários" para não-admin em `includes/navbar.php`.

- [x] **7. Usuário do banco.** ✅ Na VPS a aplicação conecta com usuário próprio (`aluguel`), não root. Credenciais via variáveis de ambiente (`DB_*`). A aplicação conecta como `root`. Criar um usuário MySQL só com `SELECT/INSERT/UPDATE/DELETE` em `aluguel_db` e usá-lo em `config/config.local.php`.

- [~] **8. SQL por concatenação.** ✅ `ajax/auth.php` e `ajax/usuarios.php` com prepared statements + whitelist de ENUM. Demais endpoints seguem escapados (migrar gradualmente). Hoje está escapado corretamente, mas é frágil. Migrar gradualmente para prepared statements (`mysqli_prepare`) ou PDO, começando por `ajax/auth.php` e `ajax/usuarios.php`. Validar ENUMs com whitelist (`status`, `nivel`, `tipo`, `estado_civil`, `tipo_conta`, `prioridade` etc.) em `ajax/usuarios.php`, `ajax/imoveis.php`, `ajax/inquilinos.php`, `ajax/proprietarios.php`, `ajax/manutencoes.php`.

- [x] **9. Mensagens de erro técnicas.** ✅ Mensagens genéricas nos endpoints; erro de conexão só no log. Vários endpoints ainda devolvem `'Erro: ' . mysqli_error($conn)` (imóveis, inquilinos, proprietários, manutenções, usuários). Trocar por `json_erro()` / mensagem genérica. `config/db.php` (`db_connect`) mostra o erro de conexão na tela: logar e mostrar mensagem genérica.

- [x] **10. Exceções do mysqli.** ✅ Handler global de exceção para `ajax/*` (JSON genérico + log). No PHP 8.1+, o mysqli lança `mysqli_sql_exception` por padrão, então os `if (!$result)` antigos nunca disparam e erros viram HTTP 500 com resposta não-JSON. Envolver os endpoints restantes em `try/catch` + `json_erro()` (padrão já usado em `ajax/contratos.php` e `ajax/contas.php`).

### 🟠 P2: Regras de negócio (precisam de decisão do dono do sistema)

- [ ] **11. Parcelas futuras de contrato encerrado ou rescindido** continuam em aberto e aparecem como a receber ou inadimplência. Sugestão: ao mudar o status para `encerrado`/`rescindido`, cancelar automaticamente as parcelas em aberto com vencimento posterior à data de encerramento (e registrar a data de encerramento; hoje não há coluna para isso).
- [ ] **12. Pagamento parcial:** pagar menos que o valor da parcela marca a parcela inteira como `pago`. Decidir: bloquear, gerar uma parcela de saldo, ou criar o status `parcial`.
- [ ] **13. Receita do mês no dashboard** (`dashboard.php`) soma só `valor_pago` (aluguel), sem multa/juros, diferente do relatório de receita. Alinhar os dois.
- [ ] **14. Primeiro mês cobrado cheio:** contrato que começa no meio do mês gera a primeira parcela com o valor integral (sem pro-rata). Confirmar a regra.
- [ ] **15. Reajuste anual:** `indices_reajuste` (IGPM/IPCA/INPC/fixo) é só gravado; nada aplica o reajuste. Definir se haverá reajuste automático ou manual.
- [ ] **16. Caução:** `caucao` e `caucao_pago` são só informativos (não geram lançamento nem devolução).
- [ ] **17. Multa de rescisão** (`multa_rescisao`) é gravada mas não é usada ao rescindir.
- [ ] **18. `manutencoes.contrato_id`** existe no banco mas nunca é preenchido. Decidir se manutenção é vinculada ao contrato (para repassar custo ao inquilino ou proprietário) ou remover a coluna.
- [ ] **19. Repasse ao proprietário:** o sistema recebe do inquilino mas não controla o repasse ao proprietário (taxa de administração, extrato). Provável próxima funcionalidade.

### 🟡 P3: Bugs menores e qualidade

- [ ] **20.** `pages/contratos.php:166`: no `editar()`, `el.value = d[f] || ""` transforma `0` em `""`; o select `caucao_pago` e campos numéricos zerados ficam vazios. Usar `d[f] ?? ""`.
- [ ] **21.** Status do imóvel editável manualmente em `pages/imoveis.php`: dá para marcar como `disponivel` um imóvel com contrato ativo. Bloquear a opção `alugado`/`disponivel` no formulário (é controlada pelos contratos) ou validar em `ajax/imoveis.php`.
- [ ] **22.** Excluir inquilino, proprietário e usuário: tratar as FKs (hoje só checa algumas relações; `recibos` também referencia `inquilino_id`).
- [ ] **23.** Recibo (`pages/recibo_view.php`): exibir o CNPJ da empresa (`$config_cnpj` é carregado na linha 28 mas nunca exibido) e valor por extenso.
- [ ] **24.** Não existe tela para editar a tabela `configuracoes` (empresa, % de multa e juros, prazo de aviso). `prazo_aviso_vencimento` não é usado (o dashboard usa 7 dias fixo).
- [ ] **25.** Código duplicado: `get_vals`/`build_sql` repetidos em cada `ajax/*.php`. Extrair um helper genérico de CRUD com whitelist de colunas.
- [ ] **26.** JS das páginas dentro de strings PHP (`$extra_js`): mover para arquivos `assets/js/<pagina>.js` e passar dados via `data-*` ou `window.X`. Elimina a classe de bug do item "JS de contas quebrado".
- [ ] **27.** `ajax/inquilinos.php` chama `db_connect()` de novo dentro de cada `case` (conexões extras sem necessidade).
- [ ] **28.** Dependências via CDN sem SRI (`integrity=`). Adicionar hashes ou servir localmente.
- [ ] **29.** Não há testes. Sugestão: PHPUnit para `includes/functions.php` (`gerar_parcelas`, `calcular_encargos`, `proximo_numero`) e testes de integração dos endpoints contra um banco de teste.
- [ ] **30.** `index.php`/`dashboard.php`: `atualizar_parcelas_atrasadas()` roda a cada requisição. Com volume maior, mover para um cron diário.

---

## 4. Migrações de banco

| Arquivo | O que faz | Aplicado em produção? |
|---|---|---|
| `migracoes/001_recibo_unico_por_parcela.sql` | `UNIQUE(parcela_id)` em `recibos` | ✅ Sim (26/09) |
| `migracoes/002_seguranca_senhas.sql` | senha VARCHAR(255), `trocar_senha`, tabela `login_tentativas` | ✅ Sim (26/09) |

Ao criar novas migrações, atualizar também o `banco.sql` (instalações novas).


---

## 5. Extras feitos no deploy (26/09)

- `pages/recibo_view.php`: include apontava para `../../includes` (quebrado) e não exigia login — corrigido (`../includes` + `require_login()`).
- `confirm()` nativo trocado por modal próprio (`confirmDelete` em `app.js`).
- **Animações** (referência "SOLE" @dailyflutterui): login com splash → marca sobe → painel desliza de baixo → dissolve ao entrar; páginas e cards entram em cascata; modais sobem (bottom sheet no celular); `prefers-reduced-motion` respeitado. CSS no fim de `assets/css/app.css` (seção MOVIMENTO).
- Deploy: Dockerfile + config por variáveis de ambiente (`DB_*`, `BASE_URL`), mantendo `config.local.php` para uso local. Produção: aluguel.lsconnect.api.br (branch `main`); homolog: homolog-aluguel.lsconnect.api.br (branch `dev`).
- **Fluxo de trabalho daqui em diante:** desenvolver em `dev` (ou branch próprio) → homolog → merge em `main` → produção.
