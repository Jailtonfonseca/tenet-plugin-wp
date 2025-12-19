# Tenet - Gerador de Conteúdo Autônomo e Inteligente

O **Tenet** é um plugin para WordPress que utiliza a inteligência artificial da OpenAI (GPT-4o) para gerar artigos completos, otimizados para SEO e integrados visualmente com imagens do Pixabay.

## 🚀 Funcionalidades

*   **Geração de Conteúdo via IA:** Cria artigos completos com formatação HTML (h2, p, ul, strong) baseados em um tópico, tom de voz e público-alvo.
*   **Integração Visual:** Busca e baixa automaticamente imagens de alta qualidade do Pixabay relacionadas ao conteúdo, definindo-as como Imagem Destacada.
*   **Módulo de Memória:** Analisa os últimos 50 posts publicados para evitar repetição de tópicos e garantir originalidade.
*   **SEO Automático:** Gera e preenche a meta descrição para plugins como Yoast SEO e Rank Math.
*   **Tags Inteligentes:** Sugere e adiciona tags relevantes ao post automaticamente.
*   **Configuração Flexível:** Permite definir o status padrão dos posts gerados (Rascunho ou Publicado).

## 📦 Instalação

1.  Faça o download do plugin ou clone este repositório na pasta `wp-content/plugins/`.
2.  Ative o plugin através do menu 'Plugins' no WordPress.
3.  Navegue até **Tenet > Configurações**.

## ⚙️ Configuração

Para utilizar o Tenet, você precisará de chaves de API da OpenAI e do Pixabay.

1.  Acesse o menu **Tenet > Configurações**.
2.  Insira sua **OpenAI API Key** (necessário para geração de texto).
3.  Insira sua **Pixabay API Key** (necessário para imagens).
4.  Defina o **Status Padrão do Post** (Rascunho recomendado).
5.  Clique em **Salvar Alterações**.

![Tela de Configurações](https://placehold.co/800x400?text=Tela+de+Configurações+do+Tenet)

## 🖥️ Uso

1.  Acesse o menu **Tenet > Tenet**.
2.  Preencha os campos:
    *   **Tópico Principal:** O assunto sobre o qual você quer escrever.
    *   **Tom de Voz:** Escolha entre Técnico, Humorístico, Jornalístico ou Acadêmico.
    *   **Público Alvo:** Defina para quem o texto é direcionado.
    *   **Instruções Extras:** Adicione detalhes específicos ou diretrizes adicionais.
3.  Clique em **Gerar Conteúdo**.
4.  Aguarde o processamento. Uma mensagem de sucesso aparecerá com o link para editar o novo post.

![Tela do Gerador](https://placehold.co/800x500?text=Tela+do+Gerador+de+Conteúdo)

## 🛠️ Requisitos

*   WordPress 5.0 ou superior.
*   PHP 7.4 ou superior.
*   Conexão com a internet para acessar as APIs da OpenAI e Pixabay.

## 📝 Licença

Este projeto está licenciado sob a licença MIT.
