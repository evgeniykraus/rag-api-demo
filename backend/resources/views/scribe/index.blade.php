<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="IE=edge,chrome=1" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Laravel API документация</title>

    <link href="https://fonts.googleapis.com/css?family=PT+Sans&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset("/vendor/scribe/css/theme-elements.style.css") }}" media="screen">

    <script src="https://cdn.jsdelivr.net/npm/lodash@4.17.10/lodash.min.js"></script>

    <link rel="stylesheet"
          href="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/styles/docco.min.css">
    <script src="https://unpkg.com/@highlightjs/cdn-assets@11.6.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
    <script type="module">
        import {CodeJar} from 'https://medv.io/codejar/codejar.js'
        window.CodeJar = CodeJar;
    </script>

            <script>
            var tryItOutBaseUrl = "http://localhost:8088";
            var useCsrf = Boolean();
            var csrfUrl = "/sanctum/csrf-cookie";
        </script>
        <script src="{{ asset("/vendor/scribe/js/tryitout-5.3.0.js") }}"></script>
        <style>
            .code-editor, .response-content {
                color: whitesmoke;
                background-color: transparent;
            }
            /*
             Problem: we want syntax highlighting for the Try It Out JSON body code editor
             However, the Try It Out area uses a dark background, while request and response samples
             (which are already highlighted) use a light background. HighlightJS can only use one theme per document.
             Our options:
             1. Change the bg of one. => No, it looks out of place on the page.
             2. Use the same highlighting for both. => Nope, one would be unreadable.
             3. Copy styles for a dark-bg h1js theme and prefix them for the CodeEditor, which is what we're doing.
             Since it's only JSON, we only need a few styles anyway.
             Styles taken from the Nord theme: https://github.com/highlightjs/highlight.js/blob/3997c9b430a568d5ad46d96693b90a74fc01ea7f/src/styles/nord.css#L2
             */
            .code-editor > .hljs-attr {
                color: #8FBCBB;
            }
            .code-editor > .hljs-string {
                color: #A3BE8C;
            }
            .code-editor > .hljs-number {
                color: #B48EAD;
            }
            .code-editor > .hljs-literal{
                color: #81A1C1;
            }

        </style>

        <script>
            function tryItOut(btnElement) {
                btnElement.disabled = true;

                let endpointId = btnElement.dataset.endpoint;

                let errorPanel = document.querySelector(`.tryItOut-error[data-endpoint=${endpointId}]`);
                errorPanel.hidden = true;
                let responsePanel = document.querySelector(`.tryItOut-response[data-endpoint=${endpointId}]`);
                responsePanel.hidden = true;

                let form = btnElement.form;
                let { method, path, hasjsonbody: hasJsonBody} = form.dataset;
                let body = {};
                if (hasJsonBody === "1") {
                    body = form.querySelector('.code-editor').textContent;
                } else if (form.dataset.hasfiles === "1") {
                    body = new FormData();
                    form.querySelectorAll('input[data-component=body]')
                        .forEach(el => {
                            if (el.type === 'file') {
                                if (el.files[0]) body.append(el.name, el.files[0])
                            } else body.append(el.name, el.value);
                        });
                } else {
                    form.querySelectorAll('input[data-component=body]').forEach(el => {
                        _.set(body, el.name, el.value);
                    });
                }

                const urlParameters = form.querySelectorAll('input[data-component=url]');
                urlParameters.forEach(el => (path = path.replace(new RegExp(`\\{${el.name}\\??}`), el.value)));

                const headers = Object.fromEntries(Array.from(form.querySelectorAll('input[data-component=header]'))
                    .map(el => [el.name, (el.dataset.prefix || '') + el.value]));

                const query = {}
                form.querySelectorAll('input[data-component=query]').forEach(el => {
                    _.set(query, el.name, el.value);
                });

                let preflightPromise = Promise.resolve();
                if (window.useCsrf && window.csrfUrl) {
                    preflightPromise = makeAPICall('GET', window.csrfUrl).then(() => {
                        headers['X-XSRF-TOKEN'] = getCookie('XSRF-TOKEN');
                    });
                }

                // content type has to be unset otherwise file upload won't work
                if (form.dataset.hasfiles === "1") {
                    delete headers['Content-Type'];
                }

                return preflightPromise.then(() => makeAPICall(method, path, body, query, headers, endpointId))
                    .then(([responseStatus, statusText, responseContent, responseHeaders]) => {
                        responsePanel.hidden = false;
                        responsePanel.querySelector(`.response-status`).textContent = responseStatus + " " + statusText ;

                        let contentEl = responsePanel.querySelector(`.response-content`);
                        if (responseContent === '') {
                            contentEl.textContent = contentEl.dataset.emptyResponseText;
                            return;
                        }

                        // Prettify it if it's JSON
                        let isJson = false;
                        try {
                            const jsonParsed = JSON.parse(responseContent);
                            if (jsonParsed !== null) {
                                isJson = true;
                                responseContent = JSON.stringify(jsonParsed, null, 4);
                            }
                        } catch (e) {}

                        // Replace HTML entities
                        responseContent = responseContent.replace(/[<>&]/g, (i) => '&#' + i.charCodeAt(0) + ';');

                        contentEl.innerHTML = responseContent;
                        isJson && window.hljs.highlightElement(contentEl);
                    })
                    .catch(err => {
                        console.log(err);
                        let errorMessage = err.message || err;
                        errorPanel.hidden = false;
                        errorPanel.querySelector(`.error-message`).textContent = errorMessage;
                    })
                    .finally(() => { btnElement.disabled = false } );
            }

            window.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('.tryItOut-btn').forEach(el => {
                    el.addEventListener('click', () => tryItOut(el));
                });
            })
        </script>
    
</head>

<body>

    <script>
        function switchExampleLanguage(lang) {
            document.querySelectorAll(`.example-request`).forEach(el => el.style.display = 'none');
            document.querySelectorAll(`.example-request-${lang}`).forEach(el => el.style.display = 'initial');
            document.querySelectorAll(`.example-request-lang-toggle`).forEach(el => el.value = lang);
        }
    </script>

<script>
    function switchExampleResponse(endpointId, index) {
        document.querySelectorAll(`.example-response-${endpointId}`).forEach(el => el.style.display = 'none');
        document.querySelectorAll(`.example-response-${endpointId}-${index}`).forEach(el => el.style.display = 'initial');
        document.querySelectorAll(`.example-response-${endpointId}-toggle`).forEach(el => el.value = index);
    }


    /*
     * Requirement: a div with class `expansion-chevrons`
     *   (or `expansion-chevrons-solid` to use the solid version).
     * Also add the `expanded` class if your div is expanded by default.
     */
    function toggleExpansionChevrons(evt) {
        let elem = evt.currentTarget;

        let chevronsArea = elem.querySelector('.expansion-chevrons');
        const solid = chevronsArea.classList.contains('expansion-chevrons-solid');
        const newState = chevronsArea.classList.contains('expanded') ? 'expand' : 'expanded';
        if (newState === 'expanded') {
            const selector = solid ? '#expanded-chevron-solid' : '#expanded-chevron';
            const template = document.querySelector(selector);
            const chevron = template.content.cloneNode(true);
            chevronsArea.replaceChildren(chevron);
            chevronsArea.classList.add('expanded');
        } else {
            const selector = solid ? '#expand-chevron-solid' : '#expand-chevron';
            const template = document.querySelector(selector);
            const chevron = template.content.cloneNode(true);
            chevronsArea.replaceChildren(chevron);
            chevronsArea.classList.remove('expanded');
        }

    }

    /**
     * 1. Make sure the children are inside the parent element
     * 2. Add `expandable` class to the parent
     * 3. Add `children` class to the children.
     * 4. Wrap the default chevron SVG in a div with class `expansion-chevrons`
     *   (or `expansion-chevrons-solid` to use the solid version).
     *   Also add the `expanded` class if your div is expanded by default.
     */
    function toggleElementChildren(evt) {
        let elem = evt.currentTarget;
        let children = elem.querySelector(`.children`);
        if (!children) return;

        if (children.contains(event.target)) return;

        let oldState = children.style.display
        if (oldState === 'none') {
            children.style.removeProperty('display');
            toggleExpansionChevrons(evt);
        } else {
            children.style.display = 'none';
            toggleExpansionChevrons(evt);
        }

        evt.stopPropagation();
    }

    function highlightSidebarItem(evt = null) {
        if (evt && evt.oldURL) {
            let oldHash = new URL(evt.oldURL).hash.slice(1);
            if (oldHash) {
                let previousItem = window['sidebar'].querySelector(`#toc-item-${oldHash}`);
                previousItem.classList.remove('sl-bg-primary-tint');
                previousItem.classList.add('sl-bg-canvas-100');
            }
        }

        let newHash = location.hash.slice(1);
        if (newHash) {
            let item = window['sidebar'].querySelector(`#toc-item-${newHash}`);
            item.classList.remove('sl-bg-canvas-100');
            item.classList.add('sl-bg-primary-tint');
        }
    }

    addEventListener('DOMContentLoaded', () => {
        highlightSidebarItem();

        document.querySelectorAll('.code-editor').forEach(elem => CodeJar(elem, (editor) => {
            // highlight.js does not trim old tags,
            // which means highlighting doesn't update on type (only on paste)
            // See https://github.com/antonmedv/codejar/issues/18
            editor.textContent = editor.textContent
            return hljs.highlightElement(editor)
        }));

        document.querySelectorAll('.expandable').forEach(el => {
            el.addEventListener('click', toggleElementChildren);
        });

        document.querySelectorAll('details').forEach(el => {
            el.addEventListener('toggle', toggleExpansionChevrons);
        });
    });

    addEventListener('hashchange', highlightSidebarItem);
</script>

<div class="sl-elements sl-antialiased sl-h-full sl-text-base sl-font-ui sl-text-body sl-flex sl-inset-0">

    <div id="sidebar" class="sl-flex sl-overflow-y-auto sl-flex-col sl-sticky sl-inset-y-0 sl-pt-8 sl-bg-canvas-100 sl-border-r"
     style="width: calc((100% - 1800px) / 2 + 300px); padding-left: calc((100% - 1800px) / 2); min-width: 300px; max-height: 100vh">
    <div class="sl-flex sl-items-center sl-mb-5 sl-ml-4">
                <h4 class="sl-text-paragraph sl-leading-snug sl-font-prose sl-font-semibold sl-text-heading">
            Laravel API документация
        </h4>
    </div>

    <div class="sl-flex sl-overflow-y-auto sl-flex-col sl-flex-grow sl-flex-shrink">
        <div class="sl-overflow-y-auto sl-w-full sl-bg-canvas-100">
            <div class="sl-my-3">
                                    <div class="expandable">
                        <div title="Introduction" id="toc-item-introduction"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#introduction"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Introduction</a>
                                                    </div>

                                            </div>
                                    <div class="expandable">
                        <div title="Authenticating requests" id="toc-item-authenticating-requests"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#authenticating-requests"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Authenticating requests</a>
                                                    </div>

                                            </div>
                                    <div class="expandable">
                        <div title="Endpoints" id="toc-item-endpoints"
                             class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-4 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none">
                            <a href="#endpoints"
                               class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0">Endpoints</a>
                                                            <div class="sl-flex sl-items-center sl-text-xs expansion-chevrons">
                                    <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                         data-icon="chevron-right"
                                         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
                                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                        <path fill="currentColor"
                                              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
                                    </svg>
                                </div>
                                                    </div>

                                                    <div class="children" style="display: none;">
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-GETapi-v1-proposals">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="GET api/v1/proposals">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-GETapi-v1-proposals">
                                                    GET api/v1/proposals
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-POSTapi-v1-proposals">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="POST api/v1/proposals">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-POSTapi-v1-proposals">
                                                    POST api/v1/proposals
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-GETapi-v1-proposals--id-">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="GET api/v1/proposals/{id}">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-GETapi-v1-proposals--id-">
                                                    GET api/v1/proposals/{id}
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-PUTapi-v1-proposals--id-">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="PUT api/v1/proposals/{id}">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-PUTapi-v1-proposals--id-">
                                                    PUT api/v1/proposals/{id}
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-DELETEapi-v1-proposals--id-">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="DELETE api/v1/proposals/{id}">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-DELETEapi-v1-proposals--id-">
                                                    DELETE api/v1/proposals/{id}
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-GETapi-v1-proposals-search">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="GET api/v1/proposals/search">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-GETapi-v1-proposals-search">
                                                    GET api/v1/proposals/search
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-GETapi-v1-dictionary-cities">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="GET api/v1/dictionary/cities">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-GETapi-v1-dictionary-cities">
                                                    GET api/v1/dictionary/cities
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                                    <div class="expandable">
                                        <div class="sl-flex sl-items-center sl-h-md sl-pr-4 sl-pl-8 sl-bg-canvas-100 hover:sl-bg-canvas-200 sl-cursor-pointer sl-select-none"
                                             id="toc-item-endpoints-GETapi-v1-dictionary-categories">
                                            <div class="sl-flex-1 sl-items-center sl-truncate sl-mr-1.5 sl-p-0" title="GET api/v1/dictionary/categories">
                                                <a class="ElementsTableOfContentsItem sl-block sl-no-underline"
                                                   href="#endpoints-GETapi-v1-dictionary-categories">
                                                    GET api/v1/dictionary/categories
                                                </a>
                                            </div>
                                                                                    </div>

                                                                            </div>
                                                            </div>
                                            </div>
                            </div>

        </div>
        <div class="sl-flex sl-items-center sl-px-4 sl-py-3 sl-border-t">
            Last updated: October 2, 2025
        </div>

        <div class="sl-flex sl-items-center sl-px-4 sl-py-3 sl-border-t">
            <a href="http://github.com/knuckleswtf/scribe">Documentation powered by Scribe ✍</a>
        </div>
    </div>
</div>

    <div class="sl-overflow-y-auto sl-flex-1 sl-w-full sl-px-16 sl-bg-canvas sl-py-16" style="max-width: 1500px;">

        <div class="sl-mb-10">
            <div class="sl-mb-4">
                <h1 class="sl-text-5xl sl-leading-tight sl-font-prose sl-font-semibold sl-text-heading">
                    Laravel API документация
                </h1>
                                    <a title="Download Postman collection" class="sl-mx-1"
                       href="{{ route("scribe.postman") }}" target="_blank">
                        <small>Postman collection →</small>
                    </a>
                                                    <a title="Download OpenAPI spec" class="sl-mx-1"
                       href="{{ route("scribe.openapi") }}" target="_blank">
                        <small>OpenAPI spec →</small>
                    </a>
                            </div>

            <div class="sl-prose sl-markdown-viewer sl-my-4">
                <h1 id="introduction">Introduction</h1>
<aside>
    <strong>Base URL</strong>: <code>http://localhost:8088</code>
</aside>
<pre><code>Эта документация предназначена для того, чтобы предоставить всю необходимую информацию для работы с нашим API.

&lt;aside&gt;Прокручивая страницу, вы увидите примеры кода для работы с API на различных языках программирования в темной области справа (или в виде части контента на мобильных устройствах).</code></pre>
<p>Вы можете сменить язык, используя вкладки в правом верхнем углу (или через меню навигации в верхней левой части на мобильных устройствах).</aside></p>

                <h1 id="authenticating-requests">Authenticating requests</h1>
<p>This API is not authenticated.</p>
            </div>
        </div>

        <h1 id="endpoints"
        class="sl-text-5xl sl-leading-tight sl-font-prose sl-text-heading"
    >
        Endpoints
    </h1>

    

                                <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-GETapi-v1-proposals">
                    GET api/v1/proposals
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/proposals"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/proposals</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/proposals"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-proposals">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-GETapi-v1-proposals"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-proposals-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-proposals-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-proposals-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-proposals-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-proposals"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-proposals"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-proposals"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8088/api/v1/proposals" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/proposals"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-proposals-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-proposals', event.target.value);">
                                                                                                            <option value="0">200</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-proposals example-response-GETapi-v1-proposals-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                            <details class="sl-pl-2">
                                                <summary style="cursor: pointer; list-style: none;">
                                                    <small>
                                                        <span class="expansion-chevrons">

    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-right"
         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
    </svg>
                                                            </span>
                                                        Headers
                                                    </small>
                                                </summary>
                                                <pre><code class="language-http">                                                            cache-control
                                                            : no-cache, private
                                                                                                                    content-type
                                                            : application/json
                                                                                                                    access-control-allow-origin
                                                            : *
                                                         </code></pre>
                                            </details>
                                                                                                                                                                        
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 810,
            &quot;content&quot;: &quot;Вот таково состояние тротуара после ремонта дороги. Сам пешеходный переход тоже весь разломан, ямы, куски асфальта. И это не единственное пострадавшее место. Такая же картина и на Комсомольской, Московской. Как преодолевать эти препятствия пожилым, больным людям, мамам с колясками? Одно делается, другое ломается.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 7,
                &quot;name&quot;: &quot;Гурьевск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 70,
                &quot;name&quot;: &quot;Наличие ям, выбоин на проезжей части, дороге&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 66,
                    &quot;name&quot;: &quot;Автомобильные дороги&quot;
                }
            }
        },
        {
            &quot;id&quot;: 811,
            &quot;content&quot;: &quot;Здравствуйте, квартира 32.  Из 8 - 3 батареи холодные. В прошлом году вызывали ваших специалистов.  Определили, что это следствие низкого давления по причине неправильной работы котельной. В этом году котельную отремонтировали. Но эти 3 батареи были тёплыми только 1 раз. Прошу увеличить давление при подаче воды.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 12,
                &quot;name&quot;: &quot;Мыски&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 31,
                &quot;name&quot;: &quot;слабое давление (напор) горячей, холодной воды&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 812,
            &quot;content&quot;: &quot;30.09 с потолка по электропроводке начала бежать вода. Была оставлена заявка в УК\&quot;Феникс\&quot;. На следующий день диспетчер сказала, что крышу обследовали, решили делать ремонт.\nВ Последующие дни протечку не устранили. Воды на полу стало больше. Завтра будет неделя, как не можем ничего добиться. Управляющая компания не принимает мер по устранению протечки.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 19,
                &quot;name&quot;: &quot;Топки&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 59,
                &quot;name&quot;: &quot;Протечка кровли (крыши) многоквартирного дома&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 813,
            &quot;content&quot;: &quot;Вопрос планируется ли установка уличного освещение по ул. Рослякова &quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 11,
                &quot;name&quot;: &quot;Мариинск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 35,
                &quot;name&quot;: &quot;предоставление электроснабжения с перебоями (ограничение)&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 814,
            &quot;content&quot;: &quot;По улице Юности когда клали асфальт убрали плитку с аллеи потом засыпали землей и так бросили траву вокруг этого болота уже совсем вытоптали с двух сторон\nВремя ответа - до 17.00 09.10.20г.\n&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 10,
                &quot;name&quot;: &quot;Ленинск-Кузнецкий&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 20,
                &quot;name&quot;: &quot;Работы по благоустройству общественной, дворовой территории выполнены с ненадлежащим качеством&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 815,
            &quot;content&quot;: &quot;Обращаются к вам собственники жилья МКД ул.Новостройка. 13А. г.Киселёвска.\n1.09.2020г. по решению суда ООО \&quot;УК\&quot;Мирт\&quot; прекратила  обслуживание МКД ул.Новостройка. 13А. Общее собрание собственников  выбрало ООО \&quot;УК\&quot;Сапфир\&quot;, но в связи с оформлением документов и  внесением  МКД ул.Новостройка. 13А. в реестр лицензий ООО \&quot;УК\&quot;Сапфир\&quot; ГЖИ  Кемеровской области  дом остался  без обслуживания накануне зимы. Пока  идёт рассмотрение и оформление  документов в ГЖИ Кемеровской  области. По распоряжению УЖКХ г. Киселёвска ООО \&quot;УК\&quot;Сапфир\&quot; уже сейчас  может обслуживать  наш дом.  Заявление по этому поводу  находится в УЖКХ, но в силу  вступить не может, т.к   некому подписать бумаги. Начальник УЖКХ болеет и  никто не хочет  взять на себя ответственность в  решении этого вопроса.\n\nПредседатель Совета  МКД ул.Новостройка. 13А.  Гаев Евгений Александрович.\n\n&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 20,
                &quot;name&quot;: &quot;Юрга&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 816,
            &quot;content&quot;: &quot;Когда с улиц нашего города уберёт бездомных собак. Район Черкасов Камень.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 6,
                &quot;name&quot;: &quot;Берёзовский&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 817,
            &quot;content&quot;: &quot;Течёт батарея в третьем подъезде, с утра звонили никто не пришёл, причём не мы одни звонили, после обеда позвонили узнать когда придут? На вопрос они ответили, что никто не прийдет!!! Бежит на втором этаже, заходишь в подъезд и на тебя капает водичка! Замечательно у нас работает жил сервис.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 16,
                &quot;name&quot;: &quot;Салаир&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 57,
                &quot;name&quot;: &quot;Повреждение элементов общего имущества многоквартирного дома: продухи, отмастки, фундамент, пол, стены, водостоки, иное&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 818,
            &quot;content&quot;: &quot;Здравствуйте. Хотелось бы знать - почему бюджетом Кемеровского муниципального округа учтены только поступления (субвенции и т.д.) для городских округов? Разве Муниципальные образования не входят в состав округа, и сколько в составе Кемеровского муниципального округа, не путать с Кемеровским городским округом, есть городов, что для них идут поступления?  &quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 5,
                &quot;name&quot;: &quot;Белово&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 819,
            &quot;content&quot;: &quot;Все стояки в квартире 38 (2 подъезд, угловой) стабильно холодные. Ситуация повторяется из года в год. УК \&quot;Жилсервис\&quot;, к которым я неоднократно обращалась, заявляет, что проблему они решить не могут, так как виновата котельная номер 23. Котельная маломощная и подаёт слабое давление и поэтому дом завоздушивается и плохо отапливается.\nПрошу помочь в решении данного вопроса и сообщить, готовится ли переключение нашего дома к центральной котельной. \nСпасибо.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 2,
                &quot;name&quot;: &quot;Кемерово&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 36,
                &quot;name&quot;: &quot;низкая температура (холодно) в жилом помещении&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 820,
            &quot;content&quot;: &quot; На детской площадке для детей с ограниченными возможностями, ул. Павлова,3. 28.09.2020 г. начато  строительство игровой зоны для детей. Проектом не предусмотрены подъездные пути к площадке. Площадка предназначена для инвалидов, вокруг площадки как видно на фотографиях и согласно проекту  нет асфальтового подъезда, только грунтовый. Это значит, что детям- колясочникам будет очень трудно попасть на площадку, а после дождя вообще невозможно. В ходе строительства это неудобство желательно устранить.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 18,
                &quot;name&quot;: &quot;Таштагол&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 12,
                &quot;name&quot;: &quot;Ненадлежащее состояние игровых и иных элементов (например, лавочек, урн, ограждений, покрытий, песка) на детской, спортивной площадке&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 821,
            &quot;content&quot;: &quot;Спасибо, за ответ на мой вопрос, об отоплении в летний период. Уточните мне, пожалуйста, а это постановление на кооперативные дома не действует? Соседний дом, кооперативный, почему они не платят за отопление летом?&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 8,
                &quot;name&quot;: &quot;Калтан&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 823,
            &quot;content&quot;: &quot;Почему СМИ и городская власть скрывают проблемы городского здравоохранения? Я прекрасно понимаю напряженность работы медиков, но чтобы ждать врача сутками, этого нет в других городах области! Не получается решить вопрос на месте - обратитесь за помощью к губернатору! Может сразу писать в приёмную президента!!!&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 4,
                &quot;name&quot;: &quot;Анжеро-Судженск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 149,
                &quot;name&quot;: &quot;Вызов врача на дом&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 132,
                    &quot;name&quot;: &quot;Здравоохранение&quot;
                }
            }
        },
        {
            &quot;id&quot;: 824,
            &quot;content&quot;: &quot;На улице Лазо , от перекрестка с ул. Березовая до перекрестка с улицей Луговой ,установлены дорожные знаки, которые давно надо убрать так как не соответствуют настоящей организации движения. Никакого движения маршрутных автобусов там нет (раньше была пятерка). Ну а уж разметки соответствующей этому знаку там не было никогда. Поэтому наличие знака дорога с полосой для маршрутного знака служит только для взыскания штрафов с водителей. И все!!! УБТС наведите  хотя бы порядок со знаками по ул. Лазо. Уберите не нужные.Отчитались же в этом году за ремонт этой дороги, хотя ремонт сделали только на 1/3  части ул. Лазо. Оставшаяся без ремонта большая часть улице вообще не соотвествует никаким критериям, еще и знаки не нужные понатыканы.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 10,
                &quot;name&quot;: &quot;Ленинск-Кузнецкий&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 71,
                &quot;name&quot;: &quot;Необходимость установка новых дорожных знаков с внесением в схему дислокации, замены старых знаков на новые&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 66,
                    &quot;name&quot;: &quot;Автомобильные дороги&quot;
                }
            }
        },
        {
            &quot;id&quot;: 825,
            &quot;content&quot;: &quot;В марте 2020 года, как начались тёплые дни, началась оттепель и начал обильно таять снег на крышах. А с крыш он прямиком начал таять в чердачное помещение и по по перекрытия вода потекла в квартиры и подъезд. Обращались с коллективной жалобой в управляющую компанию \&quot;Жилсервис\&quot;, устно обещали отремонтировать кровлю, но по сей день никаких работ не производилось! На письменные жалобы ни в УК, ни на сайте приёмной губернатора ответов нет.&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;name&quot;: &quot;Новокузнецк&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 59,
                &quot;name&quot;: &quot;Протечка кровли (крыши) многоквартирного дома&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        }
    ],
    &quot;links&quot;: {
        &quot;first&quot;: &quot;http://localhost/api/v1/proposals?page=1&quot;,
        &quot;last&quot;: &quot;http://localhost/api/v1/proposals?page=10980&quot;,
        &quot;prev&quot;: null,
        &quot;next&quot;: &quot;http://localhost/api/v1/proposals?page=2&quot;
    },
    &quot;meta&quot;: {
        &quot;current_page&quot;: 1,
        &quot;from&quot;: 1,
        &quot;last_page&quot;: 10980,
        &quot;links&quot;: [
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;pagination.previous&quot;,
                &quot;page&quot;: null,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=1&quot;,
                &quot;label&quot;: &quot;1&quot;,
                &quot;page&quot;: 1,
                &quot;active&quot;: true
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=2&quot;,
                &quot;label&quot;: &quot;2&quot;,
                &quot;page&quot;: 2,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=3&quot;,
                &quot;label&quot;: &quot;3&quot;,
                &quot;page&quot;: 3,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=4&quot;,
                &quot;label&quot;: &quot;4&quot;,
                &quot;page&quot;: 4,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=5&quot;,
                &quot;label&quot;: &quot;5&quot;,
                &quot;page&quot;: 5,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=6&quot;,
                &quot;label&quot;: &quot;6&quot;,
                &quot;page&quot;: 6,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=7&quot;,
                &quot;label&quot;: &quot;7&quot;,
                &quot;page&quot;: 7,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=8&quot;,
                &quot;label&quot;: &quot;8&quot;,
                &quot;page&quot;: 8,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=9&quot;,
                &quot;label&quot;: &quot;9&quot;,
                &quot;page&quot;: 9,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=10&quot;,
                &quot;label&quot;: &quot;10&quot;,
                &quot;page&quot;: 10,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: null,
                &quot;label&quot;: &quot;...&quot;,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=10979&quot;,
                &quot;label&quot;: &quot;10979&quot;,
                &quot;page&quot;: 10979,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=10980&quot;,
                &quot;label&quot;: &quot;10980&quot;,
                &quot;page&quot;: 10980,
                &quot;active&quot;: false
            },
            {
                &quot;url&quot;: &quot;http://localhost/api/v1/proposals?page=2&quot;,
                &quot;label&quot;: &quot;pagination.next&quot;,
                &quot;page&quot;: 2,
                &quot;active&quot;: false
            }
        ],
        &quot;path&quot;: &quot;http://localhost/api/v1/proposals&quot;,
        &quot;per_page&quot;: 15,
        &quot;to&quot;: 15,
        &quot;total&quot;: 164689
    }
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-POSTapi-v1-proposals">
                    POST api/v1/proposals
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/proposals"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: black;"
                        >
                            POST
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/proposals</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">city_id</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>ID города. The <code>id</code> of an existing record in the cities table.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        1
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">content</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>Содержание обращения. validation.max.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Текст обращения
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="POST"
              data-path="api/v1/proposals"
              data-hasfiles="0"
              data-hasjsonbody="1">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-POSTapi-v1-proposals">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-POSTapi-v1-proposals"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-proposals-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-POSTapi-v1-proposals-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-POSTapi-v1-proposals-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-POSTapi-v1-proposals-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-POSTapi-v1-proposals"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "city_id": 1,
    "content": "\u0422\u0435\u043a\u0441\u0442 \u043e\u0431\u0440\u0430\u0449\u0435\u043d\u0438\u044f"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="POSTapi-v1-proposals"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="POSTapi-v1-proposals"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="POSTapi-v1-proposals"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request POST \
    "http://localhost:8088/api/v1/proposals" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"city_id\": 1,
    \"content\": \"Текст обращения\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/proposals"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "city_id": 1,
    "content": "Текст обращения"
};

fetch(url, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-GETapi-v1-proposals--id-">
                    GET api/v1/proposals/{id}
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/proposals/{id}"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/proposals/{id}</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">URL Parameters</h3>

                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">id</div>
                                            <span class="sl-truncate sl-text-muted">integer</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The ID of the proposal.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        810
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/proposals/{id}"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-proposals--id-">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-GETapi-v1-proposals--id-"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-proposals--id--Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-proposals--id--Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-proposals--id--Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-proposals--id--Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            URL Parameters
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                            <label aria-hidden="true"
                                       for="urlparam-GETapi-v1-proposals--id--id">id</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="id" name="id"
                                               id="urlparam-GETapi-v1-proposals--id--id"
                                               placeholder="The ID of the proposal."
                                               value="810" data-component="url"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-proposals--id-"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-proposals--id-"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-proposals--id-"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8088/api/v1/proposals/810" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/proposals/810"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-proposals--id--toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-proposals--id-', event.target.value);">
                                                                                                            <option value="0">200</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-proposals--id- example-response-GETapi-v1-proposals--id--0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                            <details class="sl-pl-2">
                                                <summary style="cursor: pointer; list-style: none;">
                                                    <small>
                                                        <span class="expansion-chevrons">

    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-right"
         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
    </svg>
                                                            </span>
                                                        Headers
                                                    </small>
                                                </summary>
                                                <pre><code class="language-http">                                                            cache-control
                                                            : no-cache, private
                                                                                                                    content-type
                                                            : application/json
                                                                                                                    access-control-allow-origin
                                                            : *
                                                         </code></pre>
                                            </details>
                                                                                                                                                                        
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;id&quot;: 810,
    &quot;content&quot;: &quot;Вот таково состояние тротуара после ремонта дороги. Сам пешеходный переход тоже весь разломан, ямы, куски асфальта. И это не единственное пострадавшее место. Такая же картина и на Комсомольской, Московской. Как преодолевать эти препятствия пожилым, больным людям, мамам с колясками? Одно делается, другое ломается.&quot;,
    &quot;created_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
    &quot;updated_at&quot;: &quot;2025-10-01T11:58:31.000000Z&quot;,
    &quot;city&quot;: {
        &quot;id&quot;: 7,
        &quot;name&quot;: &quot;Гурьевск&quot;
    },
    &quot;category&quot;: {
        &quot;id&quot;: 70,
        &quot;name&quot;: &quot;Наличие ям, выбоин на проезжей части, дороге&quot;
    }
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-PUTapi-v1-proposals--id-">
                    PUT api/v1/proposals/{id}
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/proposals/{id}"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: darkblue;"
                        >
                            PUT
                        </div>
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: purple;"
                        >
                            PATCH
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/proposals/{id}</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">URL Parameters</h3>

                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">id</div>
                                            <span class="sl-truncate sl-text-muted">integer</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The ID of the proposal.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        810
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    

                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Body Parameters</h3>

                                <div class="sl-text-sm">
                                    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">city_id</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>ID города. The <code>id</code> of an existing record in the cities table.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        1
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
    <div class="expandable sl-text-sm sl-border-l sl-ml-px">
        <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">content</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>Содержание обращения. validation.max.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Текст обращения
                    </div>
                </div>
            </div>
            </div>
</div>

            </div>
                            </div>
                        </div>
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="PUT"
              data-path="api/v1/proposals/{id}"
              data-hasfiles="0"
              data-hasjsonbody="1">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-PUTapi-v1-proposals--id-">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-PUTapi-v1-proposals--id-"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-PUTapi-v1-proposals--id--Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-PUTapi-v1-proposals--id--Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-PUTapi-v1-proposals--id--Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-PUTapi-v1-proposals--id--Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            URL Parameters
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                            <label aria-hidden="true"
                                       for="urlparam-PUTapi-v1-proposals--id--id">id</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="id" name="id"
                                               id="urlparam-PUTapi-v1-proposals--id--id"
                                               placeholder="The ID of the proposal."
                                               value="810" data-component="url"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Body
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                                                    <div class="TextRequestBody sl-p-4">
                                <div class="code-editor language-json"
                                     id="json-body-PUTapi-v1-proposals--id-"
                                     style="font-family: var(--font-code); font-size: 12px; line-height: var(--lh-code);"
                                >{
    "city_id": 1,
    "content": "\u0422\u0435\u043a\u0441\u0442 \u043e\u0431\u0440\u0430\u0449\u0435\u043d\u0438\u044f"
}</div>
                            </div>
                                            </div>
                </div>
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="PUTapi-v1-proposals--id-"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="PUTapi-v1-proposals--id-"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="PUTapi-v1-proposals--id-"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request PUT \
    "http://localhost:8088/api/v1/proposals/810" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json" \
    --data "{
    \"city_id\": 1,
    \"content\": \"Текст обращения\"
}"
</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/proposals/810"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

let body = {
    "city_id": 1,
    "content": "Текст обращения"
};

fetch(url, {
    method: "PUT",
    headers,
    body: JSON.stringify(body),
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-DELETEapi-v1-proposals--id-">
                    DELETE api/v1/proposals/{id}
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/proposals/{id}"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: red;"
                        >
                            DELETE
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/proposals/{id}</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                                            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">URL Parameters</h3>

                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">id</div>
                                            <span class="sl-truncate sl-text-muted">integer</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>The ID of the proposal.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        810
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="DELETE"
              data-path="api/v1/proposals/{id}"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-DELETEapi-v1-proposals--id-">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-DELETEapi-v1-proposals--id-"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-DELETEapi-v1-proposals--id--Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-DELETEapi-v1-proposals--id--Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-DELETEapi-v1-proposals--id--Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-DELETEapi-v1-proposals--id--Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            URL Parameters
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                            <label aria-hidden="true"
                                       for="urlparam-DELETEapi-v1-proposals--id--id">id</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="id" name="id"
                                               id="urlparam-DELETEapi-v1-proposals--id--id"
                                               placeholder="The ID of the proposal."
                                               value="810" data-component="url"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="DELETEapi-v1-proposals--id-"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="DELETEapi-v1-proposals--id-"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="DELETEapi-v1-proposals--id-"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request DELETE \
    "http://localhost:8088/api/v1/proposals/810" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/proposals/810"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "DELETE",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-GETapi-v1-proposals-search">
                    GET api/v1/proposals/search
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/proposals/search"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/proposals/search</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                                                <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">
                                <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">Query Parameters</h3>

                                <div class="sl-text-sm">
                                                                            <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">query</div>
                                            <span class="sl-truncate sl-text-muted">string</span>
                                    </div>
                                    <div class="sl-flex-1 sl-h-px sl-mx-3"></div>
                    <span class="sl-ml-2 sl-text-warning">required</span>
                                    </div>
                <div class="sl-prose sl-markdown-viewer" style="font-size: 12px;">
            <p>validation.min.</p>
        </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        Не вывозят мусор неделями
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                </div>
                        </div>
                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/proposals/search"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-proposals-search">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-GETapi-v1-proposals-search"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-proposals-search-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-proposals-search-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-proposals-search-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-proposals-search-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Query Parameters
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="queryparam-GETapi-v1-proposals-search-query">query</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                                                                    <input aria-label="query" name="query"
                                                   id="queryparam-GETapi-v1-proposals-search-query"
                                                   placeholder="validation.min."
                                                   value="Не вывозят мусор неделями" data-component="query"
                                                   class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border"
                                            >
                                                                            </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-proposals-search"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-proposals-search"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-proposals-search"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8088/api/v1/proposals/search?query=%D0%9D%D0%B5+%D0%B2%D1%8B%D0%B2%D0%BE%D0%B7%D1%8F%D1%82+%D0%BC%D1%83%D1%81%D0%BE%D1%80+%D0%BD%D0%B5%D0%B4%D0%B5%D0%BB%D1%8F%D0%BC%D0%B8" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/proposals/search"
);

const params = {
    "query": "Не вывозят мусор неделями",
};
Object.keys(params)
    .forEach(key =&gt; url.searchParams.append(key, params[key]));

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-proposals-search-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-proposals-search', event.target.value);">
                                                                                                            <option value="0">200</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-proposals-search example-response-GETapi-v1-proposals-search-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                            <details class="sl-pl-2">
                                                <summary style="cursor: pointer; list-style: none;">
                                                    <small>
                                                        <span class="expansion-chevrons">

    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-right"
         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
    </svg>
                                                            </span>
                                                        Headers
                                                    </small>
                                                </summary>
                                                <pre><code class="language-http">                                                            cache-control
                                                            : no-cache, private
                                                                                                                    content-type
                                                            : application/json
                                                                                                                    access-control-allow-origin
                                                            : *
                                                         </code></pre>
                                            </details>
                                                                                                                                                                        
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">{
    &quot;data&quot;: [
        {
            &quot;id&quot;: 72811,
            &quot;content&quot;: &quot;Не вывозят мусор неделями&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T12:01:23.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T12:01:23.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 4,
                &quot;name&quot;: &quot;Анжеро-Судженск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 37,
                &quot;name&quot;: &quot;Нарушение графика вывоза твердых коммунальных отходов, в том числе с контейнерных площадок&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 75840,
            &quot;content&quot;: &quot;Не убирают мусор неделями&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T12:01:30.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T12:01:30.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 15,
                &quot;name&quot;: &quot;Прокопьевск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 4,
                &quot;name&quot;: &quot;Неубранная дворовая территория&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 102912,
            &quot;content&quot;: &quot;Мусор не вывозится неделю&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T12:02:25.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T12:02:25.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 8,
                &quot;name&quot;: &quot;Калтан&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 92,
                &quot;name&quot;: &quot;Нарушения в деятельности региональных операторов по обращению с твердыми коммунальными отходами&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 91,
                    &quot;name&quot;: &quot;Экология&quot;
                }
            }
        },
        {
            &quot;id&quot;: 132169,
            &quot;content&quot;: &quot;Больше недели не увозят мусор&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T12:03:25.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T12:03:25.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 8,
                &quot;name&quot;: &quot;Калтан&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 13601,
            &quot;content&quot;: &quot;Неделю не вывозят мусор&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:58:57.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:58:57.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 7,
                &quot;name&quot;: &quot;Гурьевск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 37,
                &quot;name&quot;: &quot;Нарушение графика вывоза твердых коммунальных отходов, в том числе с контейнерных площадок&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 128105,
            &quot;content&quot;: &quot;Не вывозят мусор по несколько дней&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T12:03:17.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T12:03:17.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 16,
                &quot;name&quot;: &quot;Салаир&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 92,
                &quot;name&quot;: &quot;Нарушения в деятельности региональных операторов по обращению с твердыми коммунальными отходами&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 91,
                    &quot;name&quot;: &quot;Экология&quot;
                }
            }
        },
        {
            &quot;id&quot;: 109450,
            &quot;content&quot;: &quot;Не вывозят мусор несколько дней&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T12:02:40.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T12:02:40.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 3,
                &quot;name&quot;: &quot;Новокузнецк&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 37,
                &quot;name&quot;: &quot;Нарушение графика вывоза твердых коммунальных отходов, в том числе с контейнерных площадок&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 27,
                    &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;
                }
            }
        },
        {
            &quot;id&quot;: 114645,
            &quot;content&quot;: &quot;Не вывозят мусор уже больше недели&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T12:02:49.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T12:02:49.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 4,
                &quot;name&quot;: &quot;Анжеро-Судженск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 28859,
            &quot;content&quot;: &quot;Уже неделю не вывозится мусор&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:59:32.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:59:32.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 20,
                &quot;name&quot;: &quot;Юрга&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 8,
                &quot;name&quot;: &quot;Несанкционированные свалки, навалы мусора на дворовой, общественной территории &quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        },
        {
            &quot;id&quot;: 33929,
            &quot;content&quot;: &quot;Мусор не вывозят две недели&quot;,
            &quot;created_at&quot;: &quot;2025-10-01T11:59:44.000000Z&quot;,
            &quot;updated_at&quot;: &quot;2025-10-01T11:59:44.000000Z&quot;,
            &quot;city&quot;: {
                &quot;id&quot;: 11,
                &quot;name&quot;: &quot;Мариинск&quot;
            },
            &quot;category&quot;: {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;,
                &quot;parent&quot;: {
                    &quot;id&quot;: 1,
                    &quot;name&quot;: &quot;Дворовые и общественные территории&quot;
                }
            }
        }
    ]
}</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-GETapi-v1-dictionary-cities">
                    GET api/v1/dictionary/cities
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/dictionary/cities"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/dictionary/cities</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/dictionary/cities"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-dictionary-cities">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-GETapi-v1-dictionary-cities"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-dictionary-cities-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-dictionary-cities-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-dictionary-cities-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-dictionary-cities-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-dictionary-cities"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-dictionary-cities"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-dictionary-cities"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8088/api/v1/dictionary/cities" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/dictionary/cities"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-dictionary-cities-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-dictionary-cities', event.target.value);">
                                                                                                            <option value="0">200</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-dictionary-cities example-response-GETapi-v1-dictionary-cities-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                            <details class="sl-pl-2">
                                                <summary style="cursor: pointer; list-style: none;">
                                                    <small>
                                                        <span class="expansion-chevrons">

    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-right"
         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
    </svg>
                                                            </span>
                                                        Headers
                                                    </small>
                                                </summary>
                                                <pre><code class="language-http">                                                            cache-control
                                                            : no-cache, private
                                                                                                                    content-type
                                                            : application/json
                                                                                                                    access-control-allow-origin
                                                            : *
                                                         </code></pre>
                                            </details>
                                                                                                                                                                        
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">[
    {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Междуреченск&quot;
    },
    {
        &quot;id&quot;: 2,
        &quot;name&quot;: &quot;Кемерово&quot;
    },
    {
        &quot;id&quot;: 3,
        &quot;name&quot;: &quot;Новокузнецк&quot;
    },
    {
        &quot;id&quot;: 4,
        &quot;name&quot;: &quot;Анжеро-Судженск&quot;
    },
    {
        &quot;id&quot;: 5,
        &quot;name&quot;: &quot;Белово&quot;
    },
    {
        &quot;id&quot;: 6,
        &quot;name&quot;: &quot;Берёзовский&quot;
    },
    {
        &quot;id&quot;: 7,
        &quot;name&quot;: &quot;Гурьевск&quot;
    },
    {
        &quot;id&quot;: 8,
        &quot;name&quot;: &quot;Калтан&quot;
    },
    {
        &quot;id&quot;: 9,
        &quot;name&quot;: &quot;Киселёвск&quot;
    },
    {
        &quot;id&quot;: 10,
        &quot;name&quot;: &quot;Ленинск-Кузнецкий&quot;
    },
    {
        &quot;id&quot;: 11,
        &quot;name&quot;: &quot;Мариинск&quot;
    },
    {
        &quot;id&quot;: 12,
        &quot;name&quot;: &quot;Мыски&quot;
    },
    {
        &quot;id&quot;: 13,
        &quot;name&quot;: &quot;Осинники&quot;
    },
    {
        &quot;id&quot;: 14,
        &quot;name&quot;: &quot;Полысаево&quot;
    },
    {
        &quot;id&quot;: 15,
        &quot;name&quot;: &quot;Прокопьевск&quot;
    },
    {
        &quot;id&quot;: 16,
        &quot;name&quot;: &quot;Салаир&quot;
    },
    {
        &quot;id&quot;: 17,
        &quot;name&quot;: &quot;Тайга&quot;
    },
    {
        &quot;id&quot;: 18,
        &quot;name&quot;: &quot;Таштагол&quot;
    },
    {
        &quot;id&quot;: 19,
        &quot;name&quot;: &quot;Топки&quot;
    },
    {
        &quot;id&quot;: 20,
        &quot;name&quot;: &quot;Юрга&quot;
    }
]</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

                    <div class="sl-stack sl-stack--vertical sl-stack--8 HttpOperation sl-flex sl-flex-col sl-items-stretch sl-w-full">
    <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
        <div class="sl-relative">
            <div class="sl-stack sl-stack--horizontal sl-stack--5 sl-flex sl-flex-row sl-items-center">
                <h2 class="sl-text-3xl sl-leading-tight sl-font-prose sl-text-heading sl-mt-5 sl-mb-1"
                    id="endpoints-GETapi-v1-dictionary-categories">
                    GET api/v1/dictionary/categories
                </h2>
            </div>
        </div>

        <div class="sl-relative">
            <div title="http://localhost:8088/api/v1/dictionary/categories"
                     class="sl-stack sl-stack--horizontal sl-stack--3 sl-inline-flex sl-flex-row sl-items-center sl-max-w-full sl-font-mono sl-py-2 sl-pr-4 sl-bg-canvas-50 sl-rounded-lg"
                >
                                            <div class="sl-text-lg sl-font-semibold sl-px-2.5 sl-py-1 sl-text-on-primary sl-rounded-lg"
                             style="background-color: green;"
                        >
                            GET
                        </div>
                                        <div class="sl-flex sl-overflow-x-hidden sl-text-lg sl-select-all">
                        <div dir="rtl"
                             class="sl-overflow-x-hidden sl-truncate sl-text-muted">http://localhost:8088</div>
                        <div class="sl-flex-1 sl-font-semibold">/api/v1/dictionary/categories</div>
                    </div>

                                                    <div class="sl-font-prose sl-font-semibold sl-px-1.5 sl-py-0.5 sl-text-on-primary sl-rounded-lg"
                                 style="background-color: darkred"
                            >requires authentication
                            </div>
                                                            </div>
        </div>

        
    </div>
    <div class="sl-flex">
        <div data-testid="two-column-left" class="sl-flex-1 sl-w-0">
            <div class="sl-stack sl-stack--vertical sl-stack--10 sl-flex sl-flex-col sl-items-stretch">
                <div class="sl-stack sl-stack--vertical sl-stack--8 sl-flex sl-flex-col sl-items-stretch">
                                            <div class="sl-stack sl-stack--vertical sl-stack--5 sl-flex sl-flex-col sl-items-stretch">
                            <h3 class="sl-text-2xl sl-leading-snug sl-font-prose">
                                Headers
                            </h3>
                            <div class="sl-text-sm">
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Content-Type</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                                    <div class="sl-flex sl-relative sl-max-w-full sl-py-2 sl-pl-3">
    <div class="sl-w-1 sl-mt-2 sl-mr-3 sl--ml-3 sl-border-t"></div>
    <div class="sl-stack sl-stack--vertical sl-stack--1 sl-flex sl-flex-1 sl-flex-col sl-items-stretch sl-max-w-full sl-ml-2 ">
        <div class="sl-flex sl-items-center sl-max-w-full">
                                        <div class="sl-flex sl-items-baseline sl-text-base">
                    <div class="sl-font-mono sl-font-semibold sl-mr-2">Accept</div>
                                    </div>
                                    </div>
                                            <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-baseline sl-text-muted">
                <span>Example:</span> <!-- <span> important for spacing -->
                <div class="sl-flex sl-flex-1 sl-flex-wrap" style="gap: 4px;">
                    <div class="sl-max-w-full sl-break-all sl-px-1 sl-bg-canvas-tint sl-text-muted sl-rounded sl-border">
                        application/json
                    </div>
                </div>
            </div>
            </div>
</div>
                                                            </div>
                        </div>
                    
                    

                    
                    
                                    </div>
            </div>
        </div>

        <div data-testid="two-column-right" class="sl-relative sl-w-2/5 sl-ml-16" style="max-width: 500px;">
            <div class="sl-stack sl-stack--vertical sl-stack--6 sl-flex sl-flex-col sl-items-stretch">

                                    <div class="sl-inverted">
    <div class="sl-overflow-y-hidden sl-rounded-lg">
        <form class="TryItPanel sl-bg-canvas-100 sl-rounded-lg"
              data-method="GET"
              data-path="api/v1/dictionary/categories"
              data-hasfiles="0"
              data-hasjsonbody="0">
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Auth
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                            <label aria-hidden="true"
                                   for="auth-GETapi-v1-dictionary-categories">api-key</label>
                            <span class="sl-mx-3">:</span>
                            <div class="sl-flex sl-flex-1">
                                <div class="sl-input sl-flex-1 sl-relative">
                                    <code></code>
                                    <input aria-label="api-key"
                                           id="auth-GETapi-v1-dictionary-categories"
                                           data-component="header"
                                           data-prefix=""
                                           name="api-key"
                                           placeholder="{API_KEY}"
                                           class="auth-value sl-relative sl-w-full sl-pr-2.5 sl-pl-2.5 sl-h-md sl-text-base sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
                            <div class="sl-panel sl-outline-none sl-w-full expandable">
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Headers
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="ParameterGrid sl-p-4">
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-dictionary-categories-Content-Type">Content-Type</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Content-Type" name="Content-Type"
                                               id="header-GETapi-v1-dictionary-categories-Content-Type"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                                                            <label aria-hidden="true"
                                       for="header-GETapi-v1-dictionary-categories-Accept">Accept</label>
                                <span class="sl-mx-3">:</span>
                                <div class="sl-flex sl-flex-1">
                                    <div class="sl-input sl-flex-1 sl-relative">
                                        <input aria-label="Accept" name="Accept"
                                               id="header-GETapi-v1-dictionary-categories-Accept"
                                               value="application/json" data-component="header"
                                               class="sl-relative sl-w-full sl-h-md sl-text-base sl-pr-2.5 sl-pl-2.5 sl-rounded sl-border-transparent hover:sl-border-input focus:sl-border-primary sl-border">
                                    </div>
                                </div>
                                                    </div>
                    </div>
                </div>
            
            
            
            
            <div class="SendButtonHolder sl-mt-4 sl-p-4 sl-pt-0">
                <div class="sl-stack sl-stack--horizontal sl-stack--2 sl-flex sl-flex-row sl-items-center">
                    <button type="button" data-endpoint="GETapi-v1-dictionary-categories"
                            class="tryItOut-btn sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-bg-primary hover:sl-bg-primary-dark active:sl-bg-primary-darker disabled:sl-bg-canvas-100 sl-text-on-primary disabled:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70"
                    >
                        Send Request 💥
                    </button>
                </div>
            </div>

            <div data-endpoint="GETapi-v1-dictionary-categories"
                 class="tryItOut-error expandable sl-panel sl-outline-none sl-w-full" hidden>
                <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                     role="button">
                    <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                        <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                            <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                 data-icon="caret-down"
                                 class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                <path fill="currentColor"
                                      d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                            </svg>
                        </div>
                        Request failed with error
                    </div>
                </div>
                <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                    <div class="sl-panel__content sl-p-4">
                        <p class="sl-pb-2"><strong class="error-message"></strong></p>
                        <p class="sl-pb-2">Tip: Check that you&#039;re properly connected to the network.
If you&#039;re a maintainer of ths API, verify that your API is running and you&#039;ve enabled CORS.
You can check the Dev Tools console for debugging information.</p>
                    </div>
                </div>
            </div>

                <div data-endpoint="GETapi-v1-dictionary-categories"
                     class="tryItOut-response expandable sl-panel sl-outline-none sl-w-full" hidden>
                    <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-4 sl-pl-3 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-cursor-pointer sl-select-none"
                         role="button">
                        <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                            <div class="sl-flex sl-items-center sl-mr-1.5 expansion-chevrons expansion-chevrons-solid expanded">
                                <svg aria-hidden="true" focusable="false" data-prefix="fas"
                                     data-icon="caret-down"
                                     class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
                                     xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                                    <path fill="currentColor"
                                          d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
                                </svg>
                            </div>
                            Received response
                        </div>
                    </div>
                    <div class="sl-panel__content-wrapper sl-bg-canvas-100 children" role="region">
                        <div class="sl-panel__content sl-p-4">
                            <p class="sl-pb-2 response-status"></p>
                            <pre><code class="sl-pb-2 response-content language-json"
                                       data-empty-response-text="<Empty response>"
                                       style="max-height: 300px;"></code></pre>
                        </div>
                    </div>
                </div>
        </form>
    </div>
</div>
                
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-h-lg">
                                    <div class="sl--ml-2">
                                        Example request:
                                        <select class="example-request-lang-toggle sl-text-base"
                                                aria-label="Request Sample Language"
                                                onchange="switchExampleLanguage(event.target.value);">
                                                                                            <option>bash</option>
                                                                                            <option>javascript</option>
                                                                                    </select>
                                    </div>
                                </div>
                            </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-bash"
                                     style="">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-bash">curl --request GET \
    --get "http://localhost:8088/api/v1/dictionary/categories" \
    --header "Content-Type: application/json" \
    --header "Accept: application/json"</code></pre>                                        </div>
                                    </div>
                                </div>
                                                            <div class="sl-bg-canvas-100 example-request example-request-javascript"
                                     style="display: none;">
                                    <div class="sl-px-0 sl-py-1">
                                        <div style="max-height: 400px;" class="sl-overflow-y-auto sl-rounded">
                                            <pre><code class="language-javascript">const url = new URL(
    "http://localhost:8088/api/v1/dictionary/categories"
);

const headers = {
    "Content-Type": "application/json",
    "Accept": "application/json",
};

fetch(url, {
    method: "GET",
    headers,
}).then(response =&gt; response.json());</code></pre>                                        </div>
                                    </div>
                                </div>
                                                    </div>
                    
                                            <div class="sl-panel sl-outline-none sl-w-full sl-rounded-lg">
                            <div class="sl-panel__titlebar sl-flex sl-items-center sl-relative focus:sl-z-10 sl-text-base sl-leading-none sl-pr-3 sl-pl-4 sl-bg-canvas-200 sl-text-body sl-border-input focus:sl-border-primary sl-select-none">
                                <div class="sl-flex sl-flex-1 sl-items-center sl-py-2">
                                    <div class="sl--ml-2">
                                        <div class="sl-h-sm sl-text-base sl-font-medium sl-px-1.5 sl-text-muted sl-rounded sl-border-transparent sl-border">
                                            <div class="sl-mb-2 sl-inline-block">Example response:</div>
                                            <div class="sl-mb-2 sl-inline-block">
                                                <select
                                                        class="example-response-GETapi-v1-dictionary-categories-toggle sl-text-base"
                                                        aria-label="Response sample"
                                                        onchange="switchExampleResponse('GETapi-v1-dictionary-categories', event.target.value);">
                                                                                                            <option value="0">200</option>
                                                                                                    </select></div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button"
                                        class="sl-button sl-h-sm sl-text-base sl-font-medium sl-px-1.5 hover:sl-bg-canvas-50 active:sl-bg-canvas-100 sl-text-muted hover:sl-text-body focus:sl-text-body sl-rounded sl-border-transparent sl-border disabled:sl-opacity-70">
                                    <div class="sl-mx-0">
                                        <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="copy"
                                             class="svg-inline--fa fa-copy fa-fw fa-sm sl-icon" role="img"
                                             xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor"
                                                  d="M384 96L384 0h-112c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48H464c26.51 0 48-21.49 48-48V128h-95.1C398.4 128 384 113.6 384 96zM416 0v96h96L416 0zM192 352V128h-144c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h192c26.51 0 48-21.49 48-48L288 416h-32C220.7 416 192 387.3 192 352z"></path>
                                        </svg>
                                    </div>
                                </button>
                            </div>
                                                            <div class="sl-panel__content-wrapper sl-bg-canvas-100 example-response-GETapi-v1-dictionary-categories example-response-GETapi-v1-dictionary-categories-0"
                                     style=" "
                                >
                                    <div class="sl-panel__content sl-p-0">                                            <details class="sl-pl-2">
                                                <summary style="cursor: pointer; list-style: none;">
                                                    <small>
                                                        <span class="expansion-chevrons">

    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-right"
         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
    </svg>
                                                            </span>
                                                        Headers
                                                    </small>
                                                </summary>
                                                <pre><code class="language-http">                                                            cache-control
                                                            : no-cache, private
                                                                                                                    content-type
                                                            : application/json
                                                                                                                    access-control-allow-origin
                                                            : *
                                                         </code></pre>
                                            </details>
                                                                                                                                                                        
                                            <pre><code style="max-height: 300px;"
                                                       class="language-json sl-overflow-x-auto sl-overflow-y-auto">[
    {
        &quot;id&quot;: 1,
        &quot;name&quot;: &quot;Дворовые и общественные территории&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 2,
                &quot;name&quot;: &quot;Нарушение правил уборки общественных территорий и тротуаров от снега и наледи&quot;
            },
            {
                &quot;id&quot;: 3,
                &quot;name&quot;: &quot;Нарушение правил уборки дворовых территорий, внутридворовых проездов и тротуаров от снега и наледи&quot;
            },
            {
                &quot;id&quot;: 4,
                &quot;name&quot;: &quot;Неубранная дворовая территория&quot;
            },
            {
                &quot;id&quot;: 5,
                &quot;name&quot;: &quot;Не работают отдельные средства освещения (фонари, лампы, иное) на общественных территориях&quot;
            },
            {
                &quot;id&quot;: 6,
                &quot;name&quot;: &quot;Не работают линии наружного освещения&quot;
            },
            {
                &quot;id&quot;: 7,
                &quot;name&quot;: &quot;Неисправные средства освещения (фонари, лампы и иное) на дворовой территории&quot;
            },
            {
                &quot;id&quot;: 8,
                &quot;name&quot;: &quot;Несанкционированные свалки, навалы мусора на дворовой, общественной территории &quot;
            },
            {
                &quot;id&quot;: 9,
                &quot;name&quot;: &quot;Незаконное размещение информационных материалов (таблички, баннеры, листовки, граффити) на столбах, деревьях, ограждениях&quot;
            },
            {
                &quot;id&quot;: 10,
                &quot;name&quot;: &quot;Ненадлежащее состояние деревьев и кустарников&quot;
            },
            {
                &quot;id&quot;: 11,
                &quot;name&quot;: &quot;Отсутствие ограждений, препятствующих заезду на зеленые насаждения, детские площадки&quot;
            },
            {
                &quot;id&quot;: 12,
                &quot;name&quot;: &quot;Ненадлежащее состояние игровых и иных элементов (например, лавочек, урн, ограждений, покрытий, песка) на детской, спортивной площадке&quot;
            },
            {
                &quot;id&quot;: 13,
                &quot;name&quot;: &quot;Ненадлежащее состояние фасадов нежилых зданий, объектов и ограждений&quot;
            },
            {
                &quot;id&quot;: 14,
                &quot;name&quot;: &quot;Парковка на газонах, зеленых насаждениях (газонах)&quot;
            },
            {
                &quot;id&quot;: 15,
                &quot;name&quot;: &quot;Разрушение тротуаров, пешеходных дорожек&quot;
            },
            {
                &quot;id&quot;: 16,
                &quot;name&quot;: &quot;Ненадлежащее содержание зеленых насаждений (газонов)&quot;
            },
            {
                &quot;id&quot;: 17,
                &quot;name&quot;: &quot;Необходимость оборудования новой детской площадки или модернизация игровых элементов действующей детской площадки&quot;
            },
            {
                &quot;id&quot;: 18,
                &quot;name&quot;: &quot;Необходимость оборудования тротуара (пешеходной дорожки) или обустройство нового покрытия тротуара (пешеходной дорожки)&quot;
            },
            {
                &quot;id&quot;: 19,
                &quot;name&quot;: &quot;Неисправность/недоступность инфраструктуры для маломобильных граждан&quot;
            },
            {
                &quot;id&quot;: 20,
                &quot;name&quot;: &quot;Работы по благоустройству общественной, дворовой территории выполнены с ненадлежащим качеством&quot;
            },
            {
                &quot;id&quot;: 21,
                &quot;name&quot;: &quot;Отсутствие лавочек на детской, спортивной площадке&quot;
            },
            {
                &quot;id&quot;: 22,
                &quot;name&quot;: &quot;Отсутствие урн на детской, спортивной площадке&quot;
            },
            {
                &quot;id&quot;: 23,
                &quot;name&quot;: &quot;Отсутствие ограждений на детской, спортивной площадке&quot;
            },
            {
                &quot;id&quot;: 24,
                &quot;name&quot;: &quot;Отсутствие покрытия на детской, спортивной площадке&quot;
            },
            {
                &quot;id&quot;: 25,
                &quot;name&quot;: &quot;Отсутствие (необходимость замены) песка в песочнице&quot;
            },
            {
                &quot;id&quot;: 26,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 27,
        &quot;name&quot;: &quot;Жилищно-коммунальное хозяйство&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 28,
                &quot;name&quot;: &quot;отсутствие отопления&quot;
            },
            {
                &quot;id&quot;: 29,
                &quot;name&quot;: &quot;отсутствие горячей воды&quot;
            },
            {
                &quot;id&quot;: 30,
                &quot;name&quot;: &quot;низкая температура горячей воды&quot;
            },
            {
                &quot;id&quot;: 31,
                &quot;name&quot;: &quot;слабое давление (напор) горячей, холодной воды&quot;
            },
            {
                &quot;id&quot;: 32,
                &quot;name&quot;: &quot;отсутствие холодной воды&quot;
            },
            {
                &quot;id&quot;: 33,
                &quot;name&quot;: &quot;высокое давление (напор) горячей, холодной воды&quot;
            },
            {
                &quot;id&quot;: 34,
                &quot;name&quot;: &quot;отсутствие электроснабжения квартиры&quot;
            },
            {
                &quot;id&quot;: 35,
                &quot;name&quot;: &quot;предоставление электроснабжения с перебоями (ограничение)&quot;
            },
            {
                &quot;id&quot;: 36,
                &quot;name&quot;: &quot;низкая температура (холодно) в жилом помещении&quot;
            },
            {
                &quot;id&quot;: 37,
                &quot;name&quot;: &quot;Нарушение графика вывоза твердых коммунальных отходов, в том числе с контейнерных площадок&quot;
            },
            {
                &quot;id&quot;: 38,
                &quot;name&quot;: &quot;Завышение платы за жилое помещение (жилищную услугу)&quot;
            },
            {
                &quot;id&quot;: 39,
                &quot;name&quot;: &quot;Завышение тарифа за коммунальные услуги (водо-, тепло-, энерго-, газоснабжение, водоотведение, обращение с твердыми коммунальными отходами)&quot;
            },
            {
                &quot;id&quot;: 40,
                &quot;name&quot;: &quot;Завышение платы за коммунальные услуги (водо-, тепло-, энерго-, газоснабжение, водоотведение, обращение с твердыми коммунальными отходами)&quot;
            },
            {
                &quot;id&quot;: 41,
                &quot;name&quot;: &quot;Ошибки в квитанции на оплату жилищно-коммунальных услуг&quot;
            },
            {
                &quot;id&quot;: 42,
                &quot;name&quot;: &quot;Отсутствие тяги в дымоходах и вентиляционных каналах&quot;
            },
            {
                &quot;id&quot;: 43,
                &quot;name&quot;: &quot;Отсутствие договора на техническое обслуживание внутридомового газового оборудования&quot;
            },
            {
                &quot;id&quot;: 44,
                &quot;name&quot;: &quot;Излишне высокая температура (жарко) в жилом помещении&quot;
            },
            {
                &quot;id&quot;: 45,
                &quot;name&quot;: &quot;Наличие коррозии газопроводов&quot;
            },
            {
                &quot;id&quot;: 46,
                &quot;name&quot;: &quot;Использование газопроводов в качестве опор для других устройств&quot;
            },
            {
                &quot;id&quot;: 47,
                &quot;name&quot;: &quot;Ненадлежащая уборка подъездов, лифтов в многоквартирном доме, в том числе нарушение периодичности уборки, наличие мусора, грязи&quot;
            },
            {
                &quot;id&quot;: 48,
                &quot;name&quot;: &quot;Неисправность лифтового оборудования&quot;
            },
            {
                &quot;id&quot;: 49,
                &quot;name&quot;: &quot;Неисправность электрооборудования в местах общего пользования многоквартирного дома (подъезды, подвалы, чердаки и другое)&quot;
            },
            {
                &quot;id&quot;: 50,
                &quot;name&quot;: &quot;Нарушено окрасочное покрытие стен, потолков подъездов многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 51,
                &quot;name&quot;: &quot;Неисправность дверей в местах общего пользования многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 52,
                &quot;name&quot;: &quot;Наледь и сосульки на кровле многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 53,
                &quot;name&quot;: &quot;Нарушение правил уборки дворовых территорий и тротуаров от снега и наледи&quot;
            },
            {
                &quot;id&quot;: 54,
                &quot;name&quot;: &quot;Самовольная установка ограждений (например, шлагбаум) на территории общего пользования многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 55,
                &quot;name&quot;: &quot;Несанкционированные надписи, рисунки, реклама на фасаде многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 56,
                &quot;name&quot;: &quot;Неисправное освещение в подъезде, дворовой территории многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 57,
                &quot;name&quot;: &quot;Повреждение элементов общего имущества многоквартирного дома: продухи, отмастки, фундамент, пол, стены, водостоки, иное&quot;
            },
            {
                &quot;id&quot;: 58,
                &quot;name&quot;: &quot;Подтопление дворовой территории многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 59,
                &quot;name&quot;: &quot;Протечка кровли (крыши) многоквартирного дома&quot;
            },
            {
                &quot;id&quot;: 60,
                &quot;name&quot;: &quot;Работы по капитальному ремонту выполнены с нарушением срока&quot;
            },
            {
                &quot;id&quot;: 61,
                &quot;name&quot;: &quot;Работы по капитальному ремонту не выполнены или выполнены частично&quot;
            },
            {
                &quot;id&quot;: 62,
                &quot;name&quot;: &quot;Работы по капитальному ремонту выполнены с ненадлежащим качеством&quot;
            },
            {
                &quot;id&quot;: 63,
                &quot;name&quot;: &quot;Неисправный мусоропровод&quot;
            },
            {
                &quot;id&quot;: 64,
                &quot;name&quot;: &quot;Некачественный текущий ремонт общего имущества многоквартирного дома (подъезды, чердаки, подвалы, дворовая территория и иное)&quot;
            },
            {
                &quot;id&quot;: 65,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 101,
        &quot;name&quot;: &quot;Общественный транспорт&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 102,
                &quot;name&quot;: &quot;Несоблюдение маршрута/графика общественного транспорта&quot;
            },
            {
                &quot;id&quot;: 103,
                &quot;name&quot;: &quot;Изменить или отменить маршрут общественного транспорта&quot;
            },
            {
                &quot;id&quot;: 104,
                &quot;name&quot;: &quot;Повреждение остановочного пункта общественного транспорта (остановка)&quot;
            },
            {
                &quot;id&quot;: 105,
                &quot;name&quot;: &quot;Грязь, мусор на остановочных пунктах общественного транспорта (остановках)&quot;
            },
            {
                &quot;id&quot;: 106,
                &quot;name&quot;: &quot;Неудовлетворительные условия проезда в общественном транспорте&quot;
            },
            {
                &quot;id&quot;: 107,
                &quot;name&quot;: &quot;Необходимость установки нового остановочного пункта общественного транспорта (остановки)&quot;
            },
            {
                &quot;id&quot;: 108,
                &quot;name&quot;: &quot;Необходимость перенести остановочный пункт общественного транспорта (остановки)&quot;
            },
            {
                &quot;id&quot;: 109,
                &quot;name&quot;: &quot;Отсутствие на остановочных пунктах общественного транспорта (остановка) информации о расписании движения общественного транспорта&quot;
            },
            {
                &quot;id&quot;: 110,
                &quot;name&quot;: &quot;Некорректное поведение водительского и кондукторского состава перевозчиков (общественного транспорта)&quot;
            },
            {
                &quot;id&quot;: 111,
                &quot;name&quot;: &quot;Завышение платы за проезд на общественном транспорте&quot;
            },
            {
                &quot;id&quot;: 112,
                &quot;name&quot;: &quot;Невыдача пассажиру билета&quot;
            },
            {
                &quot;id&quot;: 113,
                &quot;name&quot;: &quot;Нарушение перевозчиком правил дорожного движения&quot;
            },
            {
                &quot;id&quot;: 114,
                &quot;name&quot;: &quot;Отсутствие оборудования для доступа на общественный транспорт для инвалидов и иных маломобильных групп населения&quot;
            },
            {
                &quot;id&quot;: 115,
                &quot;name&quot;: &quot;Необходимость оборудования общественного транспорта элементами доступа для инвалидов и иных маломобильных групп населения&quot;
            },
            {
                &quot;id&quot;: 116,
                &quot;name&quot;: &quot;Нарушение схемы движения маршрута общественного транспорта&quot;
            },
            {
                &quot;id&quot;: 117,
                &quot;name&quot;: &quot;Необходимость добавить новый маршрут общественного транспорта&quot;
            },
            {
                &quot;id&quot;: 118,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 66,
        &quot;name&quot;: &quot;Автомобильные дороги&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 67,
                &quot;name&quot;: &quot;Нарушение графика (пробки) движения автомобильного транспорта&quot;
            },
            {
                &quot;id&quot;: 68,
                &quot;name&quot;: &quot;Необходимость очистки проезжей части, дороги от снега, наледи&quot;
            },
            {
                &quot;id&quot;: 69,
                &quot;name&quot;: &quot;Несоблюдение правил уборки проезжей части, дороги&quot;
            },
            {
                &quot;id&quot;: 70,
                &quot;name&quot;: &quot;Наличие ям, выбоин на проезжей части, дороге&quot;
            },
            {
                &quot;id&quot;: 71,
                &quot;name&quot;: &quot;Необходимость установка новых дорожных знаков с внесением в схему дислокации, замены старых знаков на новые&quot;
            },
            {
                &quot;id&quot;: 72,
                &quot;name&quot;: &quot;Некорректная разметка проезжей части&quot;
            },
            {
                &quot;id&quot;: 73,
                &quot;name&quot;: &quot;Отсутствие разметки проезжей части&quot;
            },
            {
                &quot;id&quot;: 74,
                &quot;name&quot;: &quot;Некачественно выполненный ремонт проезжей части, дороги&quot;
            },
            {
                &quot;id&quot;: 75,
                &quot;name&quot;: &quot;Необходимость проведения ремонта проезжей части, дороги&quot;
            },
            {
                &quot;id&quot;: 76,
                &quot;name&quot;: &quot;Неисправное освещение на проезжей части, дороге&quot;
            },
            {
                &quot;id&quot;: 77,
                &quot;name&quot;: &quot;Неисправный светофор&quot;
            },
            {
                &quot;id&quot;: 78,
                &quot;name&quot;: &quot;Подтопление проезжей части, дороги&quot;
            },
            {
                &quot;id&quot;: 79,
                &quot;name&quot;: &quot;Нечитаемые дорожные знаки&quot;
            },
            {
                &quot;id&quot;: 80,
                &quot;name&quot;: &quot;Наличие бесхозяйной дороги&quot;
            },
            {
                &quot;id&quot;: 81,
                &quot;name&quot;: &quot;Несанкционированные свалки, мусор на дорогах&quot;
            },
            {
                &quot;id&quot;: 82,
                &quot;name&quot;: &quot;Необходимость обустройства пешеходного перехода&quot;
            },
            {
                &quot;id&quot;: 83,
                &quot;name&quot;: &quot;Необходимость установки светофора&quot;
            },
            {
                &quot;id&quot;: 84,
                &quot;name&quot;: &quot;Неприспособленность объектов дорожной инфраструктуры к нуждам инвалидов и иных маломобильных групп населения, в том числе отсутствие необходимого оборудования или его ненадлежащее состояние&quot;
            },
            {
                &quot;id&quot;: 85,
                &quot;name&quot;: &quot;Необходимость изменения организации автомобильного движения&quot;
            },
            {
                &quot;id&quot;: 86,
                &quot;name&quot;: &quot;Отсутствие люка, решетки канализации на дороге, проезжей части&quot;
            },
            {
                &quot;id&quot;: 87,
                &quot;name&quot;: &quot;Необходимость обустройства нового лежачего полицейского (ИДН)&quot;
            },
            {
                &quot;id&quot;: 88,
                &quot;name&quot;: &quot;Неисправный лежачий полицейский (ИДН)&quot;
            },
            {
                &quot;id&quot;: 89,
                &quot;name&quot;: &quot;Поврежденные/неправильно установленные дорожные знаки&quot;
            },
            {
                &quot;id&quot;: 90,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 91,
        &quot;name&quot;: &quot;Экология&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 92,
                &quot;name&quot;: &quot;Нарушения в деятельности региональных операторов по обращению с твердыми коммунальными отходами&quot;
            },
            {
                &quot;id&quot;: 93,
                &quot;name&quot;: &quot;Нарушение в деятельности полигонов&quot;
            },
            {
                &quot;id&quot;: 94,
                &quot;name&quot;: &quot;Несоблюдение экологических требований при обращении с отходами&quot;
            },
            {
                &quot;id&quot;: 95,
                &quot;name&quot;: &quot;О представлении предложений по применению технологий на объектах по обращению с отходами&quot;
            },
            {
                &quot;id&quot;: 96,
                &quot;name&quot;: &quot;Загрязнение почв от деятельности предприятий, организаций&quot;
            },
            {
                &quot;id&quot;: 97,
                &quot;name&quot;: &quot;Сброс сточных вод или загрязняющих веществ в водные объекты&quot;
            },
            {
                &quot;id&quot;: 98,
                &quot;name&quot;: &quot;Незаконное недропользование&quot;
            },
            {
                &quot;id&quot;: 99,
                &quot;name&quot;: &quot;Выбросы вредных веществ с территории предприятий, промышленных зон&quot;
            },
            {
                &quot;id&quot;: 100,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 119,
        &quot;name&quot;: &quot;Безопасность&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 120,
                &quot;name&quot;: &quot;Брошенное транспортное средство (например, автомобиль)&quot;
            },
            {
                &quot;id&quot;: 121,
                &quot;name&quot;: &quot;Несанкционированное (незаконное, в отсутствие регистрации, иное) проживание мигрантов в жилом помещении (многоквартирный дом, индивидуальный дом)&quot;
            },
            {
                &quot;id&quot;: 122,
                &quot;name&quot;: &quot;Несанкционированное (незаконное, в отсутствие регистрации, иное) проживание мигрантов в домах и зданиях, предназначенных под снос&quot;
            },
            {
                &quot;id&quot;: 123,
                &quot;name&quot;: &quot;Неисправность систем пожаробезопасности в многоквартирных домах&quot;
            },
            {
                &quot;id&quot;: 124,
                &quot;name&quot;: &quot;Неисправность систем пожаробезопасности в административных зданиях&quot;
            },
            {
                &quot;id&quot;: 125,
                &quot;name&quot;: &quot;Отсутствие или повреждение ограждения строительной площадки&quot;
            },
            {
                &quot;id&quot;: 126,
                &quot;name&quot;: &quot;Наличие незаконных игорных заведений&quot;
            },
            {
                &quot;id&quot;: 127,
                &quot;name&quot;: &quot;Просадка люка/незакрытый люк&quot;
            },
            {
                &quot;id&quot;: 128,
                &quot;name&quot;: &quot;Наличие информации (прямой или косвенной) о продаже наркотиков&quot;
            },
            {
                &quot;id&quot;: 129,
                &quot;name&quot;: &quot;Другое&quot;
            },
            {
                &quot;id&quot;: 130,
                &quot;name&quot;: &quot;Подтопления&quot;
            },
            {
                &quot;id&quot;: 131,
                &quot;name&quot;: &quot;Бездомные животные&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 132,
        &quot;name&quot;: &quot;Здравоохранение&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 133,
                &quot;name&quot;: &quot;Оказание медицинской помощи ненадлежащего качества&quot;
            },
            {
                &quot;id&quot;: 134,
                &quot;name&quot;: &quot;Невозможность попасть на плановый прием к врачу (взрослое население)&quot;
            },
            {
                &quot;id&quot;: 135,
                &quot;name&quot;: &quot;Отсутствие льготного лекарственного средства&quot;
            },
            {
                &quot;id&quot;: 136,
                &quot;name&quot;: &quot;Некорректная работа скорой медицинской помощи&quot;
            },
            {
                &quot;id&quot;: 137,
                &quot;name&quot;: &quot;Неоправданно длительное ожидание скорой медицинской помощи&quot;
            },
            {
                &quot;id&quot;: 138,
                &quot;name&quot;: &quot;Необоснованное взимание платы за медицинские услуги&quot;
            },
            {
                &quot;id&quot;: 139,
                &quot;name&quot;: &quot;Хамство медицинских работников&quot;
            },
            {
                &quot;id&quot;: 140,
                &quot;name&quot;: &quot;Наличие проблем с обеспечением детским питанием (отсутствие, недостаточный объем, иное)&quot;
            },
            {
                &quot;id&quot;: 141,
                &quot;name&quot;: &quot;Невозможность прикрепления или неоправданно длительное прикрепление к медицинской организации&quot;
            },
            {
                &quot;id&quot;: 142,
                &quot;name&quot;: &quot;Невозможность или неоправданно длительный вызов врача на дом&quot;
            },
            {
                &quot;id&quot;: 143,
                &quot;name&quot;: &quot;Завышение цен на лекарственный препарат по сравнению с ценой, зарегистрированной в Государственном реестре лекарственных средств (ГРЛС)&quot;
            },
            {
                &quot;id&quot;: 144,
                &quot;name&quot;: &quot;Необеспечение в полном объеме лекарственными средствами и медицинскими изделиями в стационаре&quot;
            },
            {
                &quot;id&quot;: 145,
                &quot;name&quot;: &quot;Другое&quot;
            },
            {
                &quot;id&quot;: 146,
                &quot;name&quot;: &quot;Аптеки&quot;
            },
            {
                &quot;id&quot;: 147,
                &quot;name&quot;: &quot;Безбарьерная среда для инвалидов&quot;
            },
            {
                &quot;id&quot;: 148,
                &quot;name&quot;: &quot;Врачи-специалисты&quot;
            },
            {
                &quot;id&quot;: 149,
                &quot;name&quot;: &quot;Вызов врача на дом&quot;
            },
            {
                &quot;id&quot;: 150,
                &quot;name&quot;: &quot;Вызов скорой помощи&quot;
            },
            {
                &quot;id&quot;: 151,
                &quot;name&quot;: &quot;Дезинфекция&quot;
            },
            {
                &quot;id&quot;: 152,
                &quot;name&quot;: &quot;Запись на приём к врачу&quot;
            },
            {
                &quot;id&quot;: 153,
                &quot;name&quot;: &quot;Иное&quot;
            },
            {
                &quot;id&quot;: 154,
                &quot;name&quot;: &quot;Консультация&quot;
            },
            {
                &quot;id&quot;: 155,
                &quot;name&quot;: &quot;Коррупция в здравоохранении&quot;
            },
            {
                &quot;id&quot;: 156,
                &quot;name&quot;: &quot;Лекарственные препараты&quot;
            },
            {
                &quot;id&quot;: 157,
                &quot;name&quot;: &quot;Льготы&quot;
            },
            {
                &quot;id&quot;: 158,
                &quot;name&quot;: &quot;Медицинская карта&quot;
            },
            {
                &quot;id&quot;: 159,
                &quot;name&quot;: &quot;Медицинская помощь&quot;
            },
            {
                &quot;id&quot;: 160,
                &quot;name&quot;: &quot;Медицинское оборудование&quot;
            },
            {
                &quot;id&quot;: 161,
                &quot;name&quot;: &quot;Молочная кухня&quot;
            },
            {
                &quot;id&quot;: 162,
                &quot;name&quot;: &quot;Нарушение нормативов/правил&quot;
            },
            {
                &quot;id&quot;: 163,
                &quot;name&quot;: &quot;Очередь на приём к врачу&quot;
            },
            {
                &quot;id&quot;: 164,
                &quot;name&quot;: &quot;Прикрепление к медицинской организации&quot;
            },
            {
                &quot;id&quot;: 165,
                &quot;name&quot;: &quot;Содержание помещений медицинских учреждений&quot;
            },
            {
                &quot;id&quot;: 166,
                &quot;name&quot;: &quot;Справочные службы&quot;
            },
            {
                &quot;id&quot;: 167,
                &quot;name&quot;: &quot;Строительство учреждений&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 168,
        &quot;name&quot;: &quot;Образование&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 169,
                &quot;name&quot;: &quot;Взимание образовательным учреждением денежных средств, в случаях, не предусмотренных договором или законодательством&quot;
            },
            {
                &quot;id&quot;: 170,
                &quot;name&quot;: &quot;Нарушения при проведении Единого государственного экзамена (ЕГЭ)&quot;
            },
            {
                &quot;id&quot;: 171,
                &quot;name&quot;: &quot;Некорректное поведение преподавателя, воспитателя&quot;
            },
            {
                &quot;id&quot;: 172,
                &quot;name&quot;: &quot;Ненадлежащее обеспечение мер безопасности на территории образовательного учреждения&quot;
            },
            {
                &quot;id&quot;: 173,
                &quot;name&quot;: &quot;Отказ в зачислении ребенка в школу, детский сад&quot;
            },
            {
                &quot;id&quot;: 174,
                &quot;name&quot;: &quot;Отсутствие или ненадлежащее отопление (холод) детского сада,  школы&quot;
            },
            {
                &quot;id&quot;: 175,
                &quot;name&quot;: &quot;Неприспособленность образовательного учреждения  к нуждам  инвалидов и иных маломобильных групп населения&quot;
            },
            {
                &quot;id&quot;: 176,
                &quot;name&quot;: &quot;Ненадлежащая уборка снега и наледи на территории образовательного учреждения&quot;
            },
            {
                &quot;id&quot;: 177,
                &quot;name&quot;: &quot;Ненадлежащее содержание территории образовательного учреждения&quot;
            },
            {
                &quot;id&quot;: 178,
                &quot;name&quot;: &quot;Необходимость проведения ремонта в образовательном учреждении&quot;
            },
            {
                &quot;id&quot;: 179,
                &quot;name&quot;: &quot;Нахождение безнадзорных животных на территории образовательного учреждения&quot;
            },
            {
                &quot;id&quot;: 180,
                &quot;name&quot;: &quot;Некачественное питание в образовательном учреждении&quot;
            },
            {
                &quot;id&quot;: 181,
                &quot;name&quot;: &quot;Отсутствие медицинского работника в образовательном учреждении&quot;
            },
            {
                &quot;id&quot;: 182,
                &quot;name&quot;: &quot;Нехватка спортивных объектов в образовательном учреждении&quot;
            },
            {
                &quot;id&quot;: 183,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 184,
        &quot;name&quot;: &quot;Торговля&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 185,
                &quot;name&quot;: &quot;Розничная продажа алкоголя без лицензии&quot;
            },
            {
                &quot;id&quot;: 186,
                &quot;name&quot;: &quot;Розничная продажа алкоголя несовершеннолетним&quot;
            },
            {
                &quot;id&quot;: 187,
                &quot;name&quot;: &quot;Розничная продажа алкоголя в ночное время (с 23 до 8 часов)&quot;
            },
            {
                &quot;id&quot;: 188,
                &quot;name&quot;: &quot;Розничная продажа алкоголя в нестационарных объектах&quot;
            },
            {
                &quot;id&quot;: 189,
                &quot;name&quot;: &quot;Розничная продажа алкоголя в непредназначенных для этого организациях и объектах&quot;
            },
            {
                &quot;id&quot;: 190,
                &quot;name&quot;: &quot;Розничная продажа пива и пивных напитков в непредназначенных для этого организациях и объектах&quot;
            },
            {
                &quot;id&quot;: 191,
                &quot;name&quot;: &quot;Нарушение общественного порядка при розничной реализации алкоголя&quot;
            },
            {
                &quot;id&quot;: 192,
                &quot;name&quot;: &quot;Неприспособленность объектов торговли  для нужд инвалидов и иных маломобильных групп населения&quot;
            },
            {
                &quot;id&quot;: 193,
                &quot;name&quot;: &quot;Нарушение санитарных требований к организациям торговли&quot;
            },
            {
                &quot;id&quot;: 194,
                &quot;name&quot;: &quot;Продажа (розничная, оптовая) просроченных товаров&quot;
            },
            {
                &quot;id&quot;: 195,
                &quot;name&quot;: &quot;Реализация табачной продукции ближе 100 метров от образовательного учреждения&quot;
            },
            {
                &quot;id&quot;: 196,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 197,
        &quot;name&quot;: &quot;Культура&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 198,
                &quot;name&quot;: &quot;Недовольство услугами, оказываемыми учреждениями культуры&quot;
            },
            {
                &quot;id&quot;: 199,
                &quot;name&quot;: &quot;Неудовлетворительное состояние учреждений культуры&quot;
            },
            {
                &quot;id&quot;: 200,
                &quot;name&quot;: &quot;Отсутствие доступа к учреждениям культуры&quot;
            },
            {
                &quot;id&quot;: 201,
                &quot;name&quot;: &quot;Отсутствие условий для инвалидов в учреждении культуры&quot;
            },
            {
                &quot;id&quot;: 202,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 203,
        &quot;name&quot;: &quot;Социальное обслуживание и защита&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 204,
                &quot;name&quot;: &quot;Доступная среда для людей с ограниченными возможностями&quot;
            },
            {
                &quot;id&quot;: 205,
                &quot;name&quot;: &quot;Качество и доступность предоставления социальных услуг&quot;
            },
            {
                &quot;id&quot;: 206,
                &quot;name&quot;: &quot;Оказание социальной помощи&quot;
            },
            {
                &quot;id&quot;: 207,
                &quot;name&quot;: &quot;Оформление индивидуальной программы предоставления социальных услуг&quot;
            },
            {
                &quot;id&quot;: 208,
                &quot;name&quot;: &quot;Пользование услугами социального такси&quot;
            },
            {
                &quot;id&quot;: 209,
                &quot;name&quot;: &quot;Предоставление государственных пособий на детей&quot;
            },
            {
                &quot;id&quot;: 210,
                &quot;name&quot;: &quot;Предоставление компенсаций по оплате жилого помещения и коммунальных услуг&quot;
            },
            {
                &quot;id&quot;: 211,
                &quot;name&quot;: &quot;Предоставление социального обслуживания&quot;
            },
            {
                &quot;id&quot;: 212,
                &quot;name&quot;: &quot;Социальная реабилитация несовершеннолетних, попавших в трудную жизненную ситуацию&quot;
            },
            {
                &quot;id&quot;: 213,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 214,
        &quot;name&quot;: &quot;Использование COVID-сертификата&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 215,
                &quot;name&quot;: &quot;Проблема в МФЦ. Не выдают сертификат в МФЦ&quot;
            },
            {
                &quot;id&quot;: 216,
                &quot;name&quot;: &quot;Проблема в МФЦ. Не могу посетить МФЦ&quot;
            },
            {
                &quot;id&quot;: 217,
                &quot;name&quot;: &quot;Проблема на объекте торговли и услуг. Не могу посетить торговый центр&quot;
            },
            {
                &quot;id&quot;: 218,
                &quot;name&quot;: &quot;Проблема на объекте торговли и услуг. Не могу посетить объект торговли и оказания услуг (после входа в ТЦ)&quot;
            },
            {
                &quot;id&quot;: 219,
                &quot;name&quot;: &quot;Проблема на объекте торговли и услуг. Не могу посетить объект торговли и оказания услуг (отдельно расположенные)&quot;
            },
            {
                &quot;id&quot;: 220,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу посетить спортивное соревнование (международное)&quot;
            },
            {
                &quot;id&quot;: 221,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу посетить спортивное соревнование (федеральное)&quot;
            },
            {
                &quot;id&quot;: 222,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу посетить спортивное соревнование (региональное)&quot;
            },
            {
                &quot;id&quot;: 223,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу посетить спортивное соревнование (муниципальное)&quot;
            },
            {
                &quot;id&quot;: 224,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу посетить спортивное соревнование (иное)&quot;
            },
            {
                &quot;id&quot;: 225,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу купить билет на спортивное соревнование (международное)&quot;
            },
            {
                &quot;id&quot;: 226,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу купить билет на спортивное соревнование (федеральное)&quot;
            },
            {
                &quot;id&quot;: 227,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу купить билет на спортивное соревнование (региональное)&quot;
            },
            {
                &quot;id&quot;: 228,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу купить билет на спортивное соревнование (муниципальное)&quot;
            },
            {
                &quot;id&quot;: 229,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Не могу купить билет на спортивное соревнование (иное)&quot;
            },
            {
                &quot;id&quot;: 230,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Проблема с участием в спортивном соревновании (международном)&quot;
            },
            {
                &quot;id&quot;: 231,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Проблема с участием в спортивном соревновании (федеральном)&quot;
            },
            {
                &quot;id&quot;: 232,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Проблема с участием в спортивном соревновании (региональном)&quot;
            },
            {
                &quot;id&quot;: 233,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Проблема с участием в спортивном соревновании (муниципальном)&quot;
            },
            {
                &quot;id&quot;: 234,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Проблема с участием в спортивном соревновании (иное)&quot;
            },
            {
                &quot;id&quot;: 235,
                &quot;name&quot;: &quot;Проблема на спортивном объекте. Проблема с посещением фитнес-центра/спортклуба/иного спортивного объекта&quot;
            },
            {
                &quot;id&quot;: 236,
                &quot;name&quot;: &quot;Проблема на работе. Проблема с доступом к рабочему месту (государственное учреждение федеральное)&quot;
            },
            {
                &quot;id&quot;: 237,
                &quot;name&quot;: &quot;Проблема на работе. Проблема с доступом к рабочему месту (государственное учреждение региональное)&quot;
            },
            {
                &quot;id&quot;: 238,
                &quot;name&quot;: &quot;Проблема на работе. Проблема с доступом к рабочему месту (государственное учреждение муниципальное)&quot;
            },
            {
                &quot;id&quot;: 239,
                &quot;name&quot;: &quot;Проблема на работе. Проблема с доступом к рабочему месту (организации иной формы собственности)&quot;
            },
            {
                &quot;id&quot;: 240,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Нет информации об ограничениях при посещении учреждения культуры (федерального)&quot;
            },
            {
                &quot;id&quot;: 241,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Нет информации об ограничениях при посещении учреждения культуры (регионального)&quot;
            },
            {
                &quot;id&quot;: 242,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Нет информации об ограничениях при посещении учреждения культуры (муниципального)&quot;
            },
            {
                &quot;id&quot;: 243,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Нет информации об ограничениях при посещении учреждения культуры (иного)&quot;
            },
            {
                &quot;id&quot;: 244,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу посетить учреждение культуры (федеральное)&quot;
            },
            {
                &quot;id&quot;: 245,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу посетить учреждение культуры (региональное)&quot;
            },
            {
                &quot;id&quot;: 246,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу посетить учреждения культуры (муниципальное)&quot;
            },
            {
                &quot;id&quot;: 247,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу посетить учреждение культуры (иное)&quot;
            },
            {
                &quot;id&quot;: 248,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу купить билет (федеральное учреждение)&quot;
            },
            {
                &quot;id&quot;: 249,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу купить билет (региональное учреждение)&quot;
            },
            {
                &quot;id&quot;: 250,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу купить билет (муниципальное учреждение)&quot;
            },
            {
                &quot;id&quot;: 251,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу купить билет (иное учреждение)&quot;
            },
            {
                &quot;id&quot;: 252,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу вернуть деньги за билет (федеральное учреждение)&quot;
            },
            {
                &quot;id&quot;: 253,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу вернуть деньги за билет (региональное учреждение)&quot;
            },
            {
                &quot;id&quot;: 254,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу вернуть деньги за билет (муниципальное учреждение)&quot;
            },
            {
                &quot;id&quot;: 255,
                &quot;name&quot;: &quot;Проблема с учреждением культуры. Не могу вернуть деньги за билет (иное учреждение)&quot;
            },
            {
                &quot;id&quot;: 256,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением учащимися ВУЗов (федеральных)&quot;
            },
            {
                &quot;id&quot;: 257,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением учащимися ВУЗов (региональных)&quot;
            },
            {
                &quot;id&quot;: 258,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением учащимися ВУЗов (частных)&quot;
            },
            {
                &quot;id&quot;: 259,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением учащимися ВУЗов (ведомственных)&quot;
            },
            {
                &quot;id&quot;: 260,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением учащимися ССУЗов (региональных)&quot;
            },
            {
                &quot;id&quot;: 261,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением учащимися ССУЗов (муниципальных)&quot;
            },
            {
                &quot;id&quot;: 262,
                &quot;name&quot;: &quot;Проблема в образовательной организации Проблема с посещением учащимися ССУЗов (ведомственных)&quot;
            },
            {
                &quot;id&quot;: 263,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением учащимися ССУЗов (иных)&quot;
            },
            {
                &quot;id&quot;: 264,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением родителями образовательных учреждений (федеральных)&quot;
            },
            {
                &quot;id&quot;: 265,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением родителями образовательных учреждений (региональных)&quot;
            },
            {
                &quot;id&quot;: 266,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением родителями образовательных учреждений (муниципальных)&quot;
            },
            {
                &quot;id&quot;: 267,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением родителями образовательных учреждений (ведомственных)&quot;
            },
            {
                &quot;id&quot;: 268,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением родителями образовательных учреждений (частных)&quot;
            },
            {
                &quot;id&quot;: 269,
                &quot;name&quot;: &quot;Проблема в образовательной организации. Проблема с посещением родителями образовательных учреждений (иных)&quot;
            },
            {
                &quot;id&quot;: 270,
                &quot;name&quot;: &quot;Проблема с сфере туризма. Проблема с размещением в гостинице, санатории, пансионате и пр. (граждане РФ)&quot;
            },
            {
                &quot;id&quot;: 271,
                &quot;name&quot;: &quot;Проблема с сфере туризма. Проблема с размещением в гостинице, санатории, пансионате и пр. (иностранные граждане)&quot;
            },
            {
                &quot;id&quot;: 272,
                &quot;name&quot;: &quot;Проблема с сфере туризма. Проблема с посещением экскурсии, объекта туристической инфраструктуры (граждане РФ)&quot;
            },
            {
                &quot;id&quot;: 273,
                &quot;name&quot;: &quot;Проблема с сфере туризма. Проблема с посещением экскурсии, объекта туристической инфраструктуры (иностранные граждане)&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 274,
        &quot;name&quot;: &quot;Обращения военнослужащих, демобилизованных граждан и их семей&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 275,
                &quot;name&quot;: &quot;Отсутствие в аптеках лекарственных препаратов&quot;
            },
            {
                &quot;id&quot;: 276,
                &quot;name&quot;: &quot;Повышение стоимости лекарственных препаратов&quot;
            },
            {
                &quot;id&quot;: 277,
                &quot;name&quot;: &quot;Проблемы с получением льготных лекарственных препаратов и медицинских товаров&quot;
            },
            {
                &quot;id&quot;: 278,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 279,
        &quot;name&quot;: &quot;Трудовые права&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 280,
                &quot;name&quot;: &quot;Другое&quot;
            },
            {
                &quot;id&quot;: 281,
                &quot;name&quot;: &quot;Увеличение продолжительности рабочего дня (смены)&quot;
            },
            {
                &quot;id&quot;: 282,
                &quot;name&quot;: &quot;Неоплачиваемая работа в выходные/праздники&quot;
            },
            {
                &quot;id&quot;: 283,
                &quot;name&quot;: &quot;Принудительная работа в выходные/праздники&quot;
            },
            {
                &quot;id&quot;: 284,
                &quot;name&quot;: &quot;Увеличение рабочей нагрузки&quot;
            },
            {
                &quot;id&quot;: 285,
                &quot;name&quot;: &quot;Уменьшение оплаты труда&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 286,
        &quot;name&quot;: &quot;Жилищная политика&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 287,
                &quot;name&quot;: &quot;Отказ в предоставлении жилищных сертификатов переселенцам из Херсонской области&quot;
            },
            {
                &quot;id&quot;: 288,
                &quot;name&quot;: &quot;Задержка предоставления жилищных сертификатов переселенцам из Херсонской области&quot;
            },
            {
                &quot;id&quot;: 289,
                &quot;name&quot;: &quot;Нарушение порядка предоставления жилищных сертификатов переселенцам из Херсонской области&quot;
            },
            {
                &quot;id&quot;: 290,
                &quot;name&quot;: &quot;Низкое качество жилья&quot;
            },
            {
                &quot;id&quot;: 291,
                &quot;name&quot;: &quot;Другое&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 292,
        &quot;name&quot;: &quot;Промышленность&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 297,
                &quot;name&quot;: &quot;Сокращение сотрудников промышленных предприятий&quot;
            },
            {
                &quot;id&quot;: 293,
                &quot;name&quot;: &quot;Другое&quot;
            },
            {
                &quot;id&quot;: 294,
                &quot;name&quot;: &quot;Остановка производств&quot;
            },
            {
                &quot;id&quot;: 295,
                &quot;name&quot;: &quot;Принудительный перевод на режим неполной рабочей недели/неполного рабочего дня&quot;
            },
            {
                &quot;id&quot;: 296,
                &quot;name&quot;: &quot;Принуждение к увольнению, неправомерное увольнение сотрудников промышленных предприятий&quot;
            },
            {
                &quot;id&quot;: 298,
                &quot;name&quot;: &quot;Нехватка сотрудников на промышленных предприятиях&quot;
            },
            {
                &quot;id&quot;: 299,
                &quot;name&quot;: &quot;Принудительная отправка сотрудников промышленных предприятий в отпуск за свой счет&quot;
            }
        ]
    },
    {
        &quot;id&quot;: 300,
        &quot;name&quot;: &quot;Учреждение допобразования (спорт)&quot;,
        &quot;children&quot;: [
            {
                &quot;id&quot;: 301,
                &quot;name&quot;: &quot;Ликвидация/переезд организации дополнительного образования&quot;
            },
            {
                &quot;id&quot;: 302,
                &quot;name&quot;: &quot;Проблема физ. доступности организаций дополнительного образования&quot;
            },
            {
                &quot;id&quot;: 303,
                &quot;name&quot;: &quot;Отказ в предоставлении услуги организацией дополнительного образования&quot;
            },
            {
                &quot;id&quot;: 304,
                &quot;name&quot;: &quot;Неудобный график&quot;
            },
            {
                &quot;id&quot;: 305,
                &quot;name&quot;: &quot;Неудовлетворительные условия спортивной секции/кружка&quot;
            }
        ]
    }
]</code></pre>
                                                                            </div>
                                </div>
                                                    </div>
                            </div>
    </div>
</div>

            

        <div class="sl-prose sl-markdown-viewer sl-my-5">
            
        </div>
    </div>

</div>

<template id="expand-chevron">
    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-right"
         class="svg-inline--fa fa-chevron-right fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M96 480c-8.188 0-16.38-3.125-22.62-9.375c-12.5-12.5-12.5-32.75 0-45.25L242.8 256L73.38 86.63c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0l192 192c12.5 12.5 12.5 32.75 0 45.25l-192 192C112.4 476.9 104.2 480 96 480z"></path>
    </svg>
</template>

<template id="expanded-chevron">
    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="chevron-down"
         class="svg-inline--fa fa-chevron-down fa-fw sl-icon sl-text-muted"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
        <path fill="currentColor"
              d="M224 416c-8.188 0-16.38-3.125-22.62-9.375l-192-192c-12.5-12.5-12.5-32.75 0-45.25s32.75-12.5 45.25 0L224 338.8l169.4-169.4c12.5-12.5 32.75-12.5 45.25 0s12.5 32.75 0 45.25l-192 192C240.4 412.9 232.2 416 224 416z"></path>
    </svg>
</template>

<template id="expand-chevron-solid">
    <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="caret-right"
         class="svg-inline--fa fa-caret-right fa-fw sl-icon" role="img" xmlns="http://www.w3.org/2000/svg"
         viewBox="0 0 256 512">
        <path fill="currentColor"
              d="M118.6 105.4l128 127.1C252.9 239.6 256 247.8 256 255.1s-3.125 16.38-9.375 22.63l-128 127.1c-9.156 9.156-22.91 11.9-34.88 6.943S64 396.9 64 383.1V128c0-12.94 7.781-24.62 19.75-29.58S109.5 96.23 118.6 105.4z"></path>
    </svg>
</template>

<template id="expanded-chevron-solid">
    <svg aria-hidden="true" focusable="false" data-prefix="fas"
         data-icon="caret-down"
         class="svg-inline--fa fa-caret-down fa-fw sl-icon" role="img"
         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
        <path fill="currentColor"
              d="M310.6 246.6l-127.1 128C176.4 380.9 168.2 384 160 384s-16.38-3.125-22.63-9.375l-127.1-128C.2244 237.5-2.516 223.7 2.438 211.8S19.07 192 32 192h255.1c12.94 0 24.62 7.781 29.58 19.75S319.8 237.5 310.6 246.6z"></path>
    </svg>
</template>
</body>
</html>
