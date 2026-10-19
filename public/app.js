const DEMO_TOKEN = 'demo-checko-api-token';
const TOKEN_KEY = 'checko-api.token';
const DASH = '—';

const app = document.getElementById('app');
const lookup = document.getElementById('lookup');
const field = document.getElementById('q');
const hint = document.getElementById('q-hint');
const notice = document.getElementById('notice');
const access = document.getElementById('access');
const accessForm = document.getElementById('access-form');
const accessMessage = document.getElementById('access-message');
const tokenInput = document.getElementById('token');

let navigation = 0;
let dossier = null;

const token = {
    get() {
        try { return localStorage.getItem(TOKEN_KEY) || DEMO_TOKEN; } catch { return DEMO_TOKEN; }
    },
    set(value) {
        try { localStorage.setItem(TOKEN_KEY, value); } catch {}
    },
    clear() {
        try { localStorage.removeItem(TOKEN_KEY); } catch {}
    },
};

class ApiError extends Error {
    constructor(status, problem = {}, retryAfter = null) {
        super(problem.detail || 'Сервис вернул неожиданный ответ');
        this.status = status;
        this.code = problem.code ?? null;
        this.retryAfter = retryAfter;
    }
}

async function api(path, params = {}) {
    const url = new URL(path, location.origin);

    for (const [name, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== '') url.searchParams.set(name, value);
    }

    let response;

    try {
        response = await fetch(url, { headers: { Accept: 'application/json', Authorization: `Bearer ${token.get()}` } });
    } catch {
        throw new ApiError(0, { detail: 'Нет связи с сервисом. Проверьте подключение и повторите.' });
    }

    showProvenance(response.headers.get('X-Data-Source'));
    const body = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(response.status, body ?? {}, Number(response.headers.get('Retry-After')) || null);
    }

    return { data: body?.data, meta: body?.meta ?? {}, stale: response.headers.get('X-Cache') === 'STALE' };
}

function h(tag, attributes = {}, ...children) {
    const element = document.createElement(tag);

    for (const [name, value] of Object.entries(attributes ?? {})) {
        if (value === null || value === undefined || value === false) continue;
        if (name === 'class') element.className = value;
        else if (name.startsWith('on')) element.addEventListener(name.slice(2), value);
        else element.setAttribute(name, value === true ? '' : value);
    }

    element.append(...children.flat(Infinity)
        .filter((child) => child !== null && child !== undefined && child !== false && child !== '')
        .map((child) => (child instanceof Node ? child : document.createTextNode(String(child)))));

    return element;
}

function icon(name) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    svg.setAttribute('class', 'icon');
    svg.setAttribute('aria-hidden', 'true');
    use.setAttribute('href', `#i-${name}`);
    svg.append(use);

    return svg;
}

const integer = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 });
const decimal = new Intl.NumberFormat('ru-RU', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
const minus = (text) => text.replace('-', '−');

const format = {
    date: (iso) => (iso ? iso.slice(0, 10).split('-').reverse().join('.') : null),
    moment(iso) {
        const moment = iso ? new Date(iso) : null;
        if (!moment || Number.isNaN(moment.getTime())) return null;
        return `${moment.toLocaleDateString('ru-RU')} в ${moment.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })}`;
    },
    rubles: (value) => (value == null ? null : `${minus(integer.format(value))} руб.`),
    millions: (value) => (value == null ? null : value < 0 ? `(${decimal.format(-value / 1e6)})` : decimal.format(value / 1e6)),
    percent: (value, signed = false) => (value == null ? null : `${signed && value > 0 ? '+' : ''}${minus(decimal.format(Number((value * 100).toFixed(2))))} %`),
    quotes: (text) => (text ? text.replace(/"([^"]*)"/g, '«$1»') : text),
};

function plural(count, [one, few, many]) {
    const tens = count % 100;
    const units = count % 10;
    if (units === 1 && tens !== 11) return one;
    if (units >= 2 && units <= 4 && (tens < 12 || tens > 14)) return few;
    return many;
}

const counted = (count, forms) => `${integer.format(count)} ${plural(count, forms)}`;
const capitalize = (text) => text.charAt(0).toUpperCase() + text.slice(1);

const IDENTIFIERS = {
    10: ['companies', 'ИНН организации'],
    12: ['entrepreneurs', 'ИНН предпринимателя'],
    13: ['companies', 'ОГРН организации'],
    15: ['entrepreneurs', 'ОГРНИП'],
};

function classify(raw) {
    const text = raw.trim();
    const digits = text.replace(/[\s-]/g, '');

    if (text === '') return { kind: 'empty', hint: '' };

    if (/^\d+$/.test(digits)) {
        const known = IDENTIFIERS[digits.length];
        return known
            ? { kind: 'id', resource: known[0], id: digits, hint: `Похоже на ${known[1]}, откроется карточка` }
            : { kind: 'invalid', hint: `${counted(digits.length, ['цифра', 'цифры', 'цифр'])}: у ИНН 10 или 12 цифр, у ОГРН 13, у ОГРНИП 15` };
    }

    if ([...text].length < 4) return { kind: 'invalid', hint: 'Для поиска по названию нужно хотя бы 4 символа' };

    return { kind: 'name', query: text, hint: 'Поиск по названию' };
}

function setHint(message = '', isError = false) {
    hint.textContent = message;
    hint.classList.toggle('is-error', isError);
    if (isError) field.setAttribute('aria-invalid', 'true');
    else field.removeAttribute('aria-invalid');
}

function syncField(value) {
    field.value = value;
    setHint();
}

function go(hash) {
    if (location.hash === hash) route();
    else location.hash = hash;
}

function setTitle(title) {
    document.title = title ? `${title} — Проверка контрагента` : 'Проверка контрагента';
}

function showProvenance(source) {
    if (source !== 'fixtures' && source !== 'live') return;
    notice.hidden = source !== 'fixtures';

    if (source === 'fixtures' && notice.childElementCount === 0) {
        notice.append(icon('info'), h('p', {},
            h('strong', {}, 'Демо-режим. '),
            'Сервис запущен без ключа источника и показывает записанные ответы реестра: ПАО Сбербанк, ООО «Яндекс» и ИП Морозов А. С. Поиск всегда возвращает одну и ту же выдачу.'));
    }
}

function openAccess(message) {
    if (message) accessMessage.textContent = message;
    access.showPopover?.();
}

const SAMPLES = [
    { name: 'ПАО Сбербанк', id: '7707083893', href: '#/companies/7707083893', kind: 'Организация', note: '88 филиалов, бухгалтерская отчётность по общим формам не публикуется' },
    { name: 'ООО «Яндекс»', id: '7736207543', href: '#/companies/7736207543', kind: 'Организация', note: 'Сведения о руководителе и учредителях закрыты в реестре' },
    { name: 'ИП Морозов А. С.', id: '402907671076', href: '#/entrepreneurs/402907671076', kind: 'Предприниматель', note: 'Калужская область, упрощённая система налогообложения' },
];

async function renderStart() {
    dossier = null;
    syncField('');
    setTitle();

    app.replaceChildren(h('div', { class: 'start' },
        h('section', { class: 'sheet' },
            h('div', { class: 'sheet-body' },
                h('h1', {}, 'Проверка контрагента по ЕГРЮЛ и ЕГРИП'),
                h('p', { class: 'lead' }, 'Введите в поле наверху ИНН, ОГРН, ОГРНИП или название. Карточка покажет состояние в реестре, руководство, признаки риска и отчётность по годам.')),
            h('div', { class: 'samples' }, SAMPLES.map((sample) => h('a', { class: 'sample', href: sample.href },
                h('strong', {}, sample.name),
                h('span', {}, sample.kind, ' · ', h('span', { class: 'num' }, sample.id)),
                h('span', {}, sample.note))))),
        h('aside', { class: 'sheet' },
            h('div', { class: 'sheet-head' }, h('h2', {}, 'Что проверяется')),
            h('table', { class: 'table' }, h('tbody', {}, [
                'Состояние в реестре и даты регистрации и ликвидации',
                'Руководство, учредители и управляющая организация',
                'Санкции, банкротство, реестр недобросовестных поставщиков',
                'Бухгалтерская отчётность и расчётные показатели',
            ].map((text) => h('tr', {}, h('td', {}, text))))))));

    field.focus();
}

async function renderSearch({ params, ticket }) {
    const query = (params.get('q') ?? '').trim();
    const target = params.get('obj') === 'ent' ? 'ent' : 'org';
    const page = Math.max(1, Number.parseInt(params.get('page') ?? '1', 10) || 1);

    dossier = null;
    syncField(query);
    setTitle(`Поиск «${query}»`);
    app.replaceChildren(skeletonSheet(8));

    const { data: items, meta } = await api('/v1/search', { by: 'name', query, obj: target, page, limit: 20 });
    if (ticket !== navigation) return;

    const pagination = meta.pagination;
    const href = (nextTarget, nextPage = 1) => `#/search?${new URLSearchParams({ q: query, obj: nextTarget, page: String(nextPage) })}`;

    app.replaceChildren(
        h('div', { class: 'results-head' },
            h('div', {}, h('h1', {}, `Поиск «${query}»`), h('p', {}, foundText(pagination))),
            h('nav', { class: 'segmented', 'aria-label': 'Кого искать' },
                h('a', { href: href('org'), 'aria-current': target === 'org' ? 'page' : null }, 'Организации'),
                h('a', { href: href('ent'), 'aria-current': target === 'ent' ? 'page' : null }, 'Предприниматели'))),
        h('section', { class: 'sheet', 'aria-label': 'Результаты поиска' },
            items.length === 0
                ? emptyBlock('Ничего не найдено', 'Проверьте написание или поищите по ИНН. Для предпринимателей переключитесь на вкладку «Предприниматели».')
                : h('div', { class: 'scroll-x' }, target === 'org' ? companyHits(items) : entrepreneurHits(items)),
            pager(pagination, (nextPage) => href(target, nextPage))));
}

function foundText(pagination) {
    if (!pagination || pagination.total == null) return '';
    if (pagination.total >= 1000) return 'Найдено 1000 и более записей, показаны первые по релевантности';
    return `Найдено: ${counted(pagination.total, ['запись', 'записи', 'записей'])}`;
}

function hitTone(hit) {
    if (!hit.is_active) return 'bad';
    return /реорганиз|ликвидац|банкрот|процесс/i.test(hit.status ?? '') ? 'warn' : 'ok';
}

function statusMark(text, tone) {
    return h('span', { class: `mark tone-${tone}` }, text);
}

function rowLink(href) {
    return (event) => {
        if (event.target.closest('a') || getSelection()?.toString()) return;
        location.hash = href;
    };
}

function companyHits(items) {
    return h('table', { class: 'table' },
        h('thead', {}, h('tr', {}, ['Наименование', 'ИНН', 'ОГРН', 'Регион', 'Состояние', 'Деятельность'].map((title) => h('th', { scope: 'col' }, title)))),
        h('tbody', {}, items.map((hit) => {
            const href = `#/companies/${hit.inn}`;
            return h('tr', { class: 'is-link', onclick: rowLink(href) },
                h('td', {}, h('a', { href }, format.quotes(hit.name.short ?? hit.name.full)), hit.address && h('div', { class: 'muted' }, hit.address)),
                h('td', { class: 'num' }, hit.inn),
                h('td', { class: 'num' }, hit.ogrn),
                h('td', { class: 'num' }, hit.region_code ?? DASH),
                h('td', {}, statusMark(hit.status ?? (hit.is_active ? 'Действует' : 'Не действует'), hitTone(hit))),
                h('td', { class: 'muted' }, hit.activity ?? DASH));
        })));
}

function entrepreneurHits(items) {
    return h('table', { class: 'table' },
        h('thead', {}, h('tr', {}, ['Предприниматель', 'ИНН', 'ОГРНИП', 'Регион', 'Состояние', 'Деятельность'].map((title) => h('th', { scope: 'col' }, title)))),
        h('tbody', {}, items.map((hit) => {
            const href = `#/entrepreneurs/${hit.inn}`;
            return h('tr', { class: 'is-link', onclick: rowLink(href) },
                h('td', {}, h('a', { href }, hit.full_name ?? DASH), hit.kind && h('div', { class: 'muted' }, hit.kind)),
                h('td', { class: 'num' }, hit.inn),
                h('td', { class: 'num' }, hit.ogrnip),
                h('td', { class: 'num' }, hit.region_code ?? DASH),
                h('td', {}, statusMark(hit.status ?? (hit.is_active ? 'Действует' : 'Прекращена'), hitTone(hit))),
                h('td', { class: 'muted' }, hit.activity ?? DASH));
        })));
}

function pager(pagination, hrefFor) {
    if (!pagination || pagination.pages_total <= 1) return null;

    const { page, per_page: perPage, total, pages_total: pagesTotal, has_next: hasNext } = pagination;
    const from = (page - 1) * perPage + 1;
    const to = total == null ? page * perPage : Math.min(page * perPage, total);
    const range = total == null
        ? `Страница ${page} из ${pagesTotal}`
        : `${integer.format(from)}–${integer.format(to)} из ${integer.format(total)}${total >= 1000 ? '+' : ''}`;

    return h('nav', { class: 'pager', 'aria-label': 'Страницы' },
        h('span', { class: 'num' }, range),
        h('div', { class: 'pager-actions' },
            h('a', { class: 'btn btn-sm', href: page > 1 ? hrefFor(page - 1) : null, 'aria-disabled': page > 1 ? null : 'true' }, icon('prev'), 'Назад'),
            h('a', { class: 'btn btn-sm', href: hasNext ? hrefFor(page + 1) : null, 'aria-disabled': hasNext ? null : 'true' }, 'Дальше', icon('next'))));
}

function emptyBlock(title, text, ...actions) {
    return h('div', { class: 'empty' }, h('h2', {}, title), h('p', {}, text), actions.length > 0 && h('div', { class: 'row' }, actions));
}

function skeletonSheet(rows) {
    return h('section', { class: 'sheet', 'aria-busy': 'true', 'aria-label': 'Загрузка' },
        h('div', { class: 'sheet-head' }, h('span', { class: 'skeleton', style: 'width: 180px' })),
        h('div', { class: 'sheet-body' }, Array.from({ length: rows }, (_, index) => h('span', { class: 'skeleton', style: `width: ${92 - (index % 3) * 14}%` }))));
}

function skeletonDossier() {
    return h('div', { class: 'dossier', 'aria-busy': 'true' },
        h('div', { class: 'stack' }, skeletonSheet(6), skeletonSheet(4)),
        skeletonSheet(6));
}

function statusOf(status, terminated, kind) {
    const name = status?.name ?? '';

    if (terminated) return { tone: 'bad', stamp: kind === 'company' ? 'Ликвидирована' : 'Прекращена' };
    if (/недейств|не действ/i.test(name)) return { tone: 'bad', stamp: 'Не действует' };
    if (/реорганиз/i.test(name)) return { tone: 'warn', stamp: 'Реорганизация' };
    if (/ликвидац/i.test(name)) return { tone: 'warn', stamp: 'Ликвидация' };
    if (/банкрот/i.test(name)) return { tone: 'warn', stamp: 'Банкротство' };
    if (name === '') return { tone: 'neutral', stamp: 'Нет сведений' };
    if (/действ/i.test(name)) return { tone: 'ok', stamp: 'Действует' };

    return { tone: 'warn', stamp: name.length <= 24 ? name : 'Особый статус' };
}

function stateGraph(status, termination, kind) {
    const { tone, stamp } = statusOf(status, termination, kind);
    const basis = [];

    if (status?.name && status.name.toLowerCase() !== stamp.toLowerCase()) basis.push(status.name);
    if (termination?.reason) basis.push(termination.reason);
    if (status?.since) basis.push(`запись от ${format.date(status.since)}`);

    return graph('Состояние', h('div', { class: 'state' },
        h('span', { class: `stamp tone-${tone}` }, stamp),
        basis.length > 0 && h('span', { class: 'basis' }, capitalize(basis.join(', ')))), 'graph-wide');
}

function graph(label, content, variant = '') {
    return h('div', { class: `graph ${variant}`.trim() }, h('dt', { class: 'label' }, label), h('dd', {}, content ?? DASH));
}

const COMPANY_CHECKS = [
    ['sanctions', 'санкции'],
    ['sanctioned_founders', 'санкции у учредителей'],
    ['unreliable_supplier', 'реестр недобросовестных поставщиков'],
    ['disqualified_persons', 'дисквалифицированные лица'],
    ['mass_director', 'массовый руководитель'],
    ['mass_founder', 'массовый учредитель'],
    ['illegal_finance', 'нелегальная финансовая деятельность'],
];

function verdict(card) {
    const found = [];
    const checked = [];

    for (const [key, title] of COMPANY_CHECKS) {
        const flag = card.risks?.[key];
        if (flag == null) continue;
        checked.push(title);
        if (flag) found.push(capitalize(title));
    }

    checked.push('сообщения о банкротстве');
    if (card.risks?.bankruptcy_messages > 0) found.push(`Сообщения о банкротстве: ${card.risks.bankruptcy_messages}`);
    if (card.address?.is_unreliable) found.push('Адрес отмечен как недостоверный');

    for (const manager of card.managers ?? []) {
        if (manager.is_disqualified) found.push('Руководитель дисквалифицирован');
        if (manager.is_unreliable) found.push('Сведения о руководителе недостоверны');
    }

    return h('div', { class: 'verdict' },
        found.length > 0
            ? [h('p', { class: 'verdict-title tone-bad' }, icon('alert'), 'Найдены признаки риска'),
                h('ul', { class: 'chips' }, found.map((title) => h('li', { class: 'chip tone-bad' }, title)))]
            : [h('p', { class: 'verdict-title tone-ok' }, icon('check'), 'Признаков риска не найдено'),
                h('p', { class: 'checked' }, `Проверено: ${checked.join(', ')}.`)]);
}

function staleNote(result) {
    if (!result.stale) return null;
    const moment = format.moment(result.meta.fetched_at);
    return h('p', { class: 'stale', role: 'status' }, icon('alert'),
        `Реестр сейчас не отвечает. Показаны сведения, полученные ${moment ?? 'ранее'}.`);
}

function companyCover(result) {
    const card = result.data;

    return h('section', { class: 'sheet', 'aria-labelledby': 'cover-title' },
        h('div', { class: 'cover-head' },
            h('h1', { id: 'cover-title' }, 'Дело №'),
            h('span', { class: 'num' }, card.ogrn),
            card.extracted_at && h('span', { class: 'meta' }, `Выписка от ${format.date(card.extracted_at)}`)),
        staleNote(result),
        h('dl', { class: 'graphs' },
            graph('Наименование', [h('span', { class: 'company-name' }, format.quotes(card.name.short ?? card.name.full)),
                card.name.full && h('span', { class: 'company-full' }, format.quotes(card.name.full))], 'graph-wide'),
            graph('ИНН / КПП', h('span', { class: 'num' }, `${card.inn} / ${card.kpp ?? DASH}`), 'graph-id'),
            graph('Начато', h('span', { class: 'num' }, format.date(card.registered_at) ?? DASH)),
            graph('Окончено', h('span', { class: 'num' }, format.date(card.liquidation?.date) ?? DASH)),
            stateGraph(card.status, card.liquidation, 'company')),
        verdict(card));
}

const SECTIONS = {
    trademarks: 'Товарные знаки',
    licenses: 'Лицензии',
    branches: 'Филиалы и представительства',
    founders: 'Учредители',
};

function inventory(base, rows, current) {
    return h('nav', { class: 'sheet inventory', 'aria-label': 'Опись' },
        h('div', { class: 'sheet-head' }, h('h2', {}, 'Опись'), h('span', { class: 'meta' }, 'листов')),
        h('ol', {}, rows.map((row, index) => {
            const cells = [h('span', { class: 'no num' }, index + 1), h('span', { class: 'title' }, row.title), h('span', { class: 'count num' }, row.count ?? '')];
            const inert = row.count === 0 || row.inert;
            return h('li', {}, inert
                ? h('span', { class: 'disabled', title: row.count === 0 ? 'Записей нет' : 'Список раскрывается только у организаций' }, cells)
                : h('a', { href: row.key === 'card' ? base : `${base}/${row.key}`, 'aria-current': row.key === current ? 'page' : null, 'data-section': row.key }, cells));
        })));
}

function setInventoryCount(section, count) {
    const cell = app.querySelector(`.inventory a[data-section="${section}"] .count`);
    if (cell) cell.textContent = String(count);
}

function markInventory(current) {
    for (const link of app.querySelectorAll('.inventory a')) {
        if (link.dataset.section === current) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    }
}

async function renderCompany({ match, params, ticket }) {
    const [, id, section = 'card'] = match;
    const base = `#/companies/${id}`;
    syncField(id);

    if (dossier?.resource !== 'companies' || dossier.id !== id) {
        setTitle(`Организация ${id}`);
        app.replaceChildren(skeletonDossier());
        const result = await api(`/v1/companies/${id}`);
        if (ticket !== navigation) return;
        dossier = { resource: 'companies', id, result };

        const sizes = result.data.collections ?? {};
        app.replaceChildren(h('div', { class: 'dossier' },
            h('div', { class: 'stack' }, companyCover(result), h('div', { class: 'stack', id: 'section' })),
            inventory(base, [
                { key: 'card', title: 'Реквизиты и руководство', count: DASH },
                { key: 'finances', title: 'Бухгалтерская отчётность', count: DASH },
                ...Object.entries(SECTIONS).map(([key, title]) => ({ key, title, count: sizes[key] ?? 0 })),
            ], section)));
    }

    setTitle(format.quotes(dossier.result.data.name.short ?? dossier.result.data.name.full));
    markInventory(section);
    if (dossier.financeYears !== undefined) setInventoryCount('finances', dossier.financeYears);
    const container = document.getElementById('section');

    try {
        if (section === 'card') container.replaceChildren(...companySheets(dossier.result.data));
        else if (section === 'finances') await renderFinances(container, id, ticket);
        else await renderCollection(container, id, section, params, ticket);
    } catch (error) {
        if (ticket === navigation) container.replaceChildren(problemSheet(error));
    }
}

function managementSheet(managers, managingOrganization) {
    const restricted = managers.some((manager) => manager.access_restricted);
    const rows = managers.map((manager) => h('tr', {},
        h('td', {}, manager.access_restricted
            ? h('span', { class: 'stamp stamp-sm tone-restricted' }, 'Сведения закрыты')
            : [manager.name ?? DASH, manager.inn && h('div', { class: 'muted' }, 'ИНН ', h('span', { class: 'num' }, manager.inn))]),
        h('td', { 'data-label': 'Должность' }, manager.position ? capitalize(manager.position.toLowerCase()) : DASH),
        h('td', { class: 'num', 'data-label': 'В должности с' }, format.date(manager.since) ?? DASH)));

    if (managingOrganization) {
        rows.push(h('tr', {},
            h('td', {}, format.quotes(managingOrganization.name) ?? DASH, h('div', { class: 'muted' }, 'ОГРН ', h('span', { class: 'num' }, managingOrganization.ogrn ?? DASH))),
            h('td', { 'data-label': 'Роль' }, 'Управляющая организация'),
            h('td', { class: 'num', 'data-label': 'С' }, DASH)));
    }

    return h('section', { class: 'sheet', 'aria-labelledby': 'management-title' },
        h('div', { class: 'sheet-head' }, h('h2', { id: 'management-title' }, 'Руководство')),
        rows.length === 0
            ? emptyBlock('Руководитель не указан', 'В реестре нет сведений о лице, действующем без доверенности.')
            : h('div', { class: 'scroll-x' }, h('table', { class: 'table people' },
                h('thead', {}, h('tr', {}, ['Руководитель', 'Должность', 'В должности с'].map((title) => h('th', { scope: 'col' }, title)))),
                h('tbody', {}, rows))),
        restricted && h('p', { class: 'sheet-note' }, 'Отметка «сведения закрыты» означает, что реестр не раскрывает эти данные. Запись о руководителе при этом есть.'));
}

function factsSheet(id, title, facts) {
    return h('section', { class: 'sheet', 'aria-labelledby': id },
        h('div', { class: 'sheet-head' }, h('h2', { id }, title)),
        h('table', { class: 'table facts' }, h('tbody', {}, facts
            .filter(([, value]) => value !== undefined)
            .map(([label, value]) => h('tr', {}, h('th', { scope: 'row' }, label), h('td', {}, value ?? DASH))))));
}

function activities(main, additional) {
    return [
        main ? [h('span', { class: 'num' }, main.code), ' ', main.name] : DASH,
        additional.length > 0 && h('details', {},
            h('summary', {}, icon('next'), `Ещё ${counted(additional.length, ['вид', 'вида', 'видов'])} деятельности`),
            h('ul', { class: 'list-plain' }, additional.map((activity) => h('li', {}, h('span', { class: 'num' }, activity.code), ' ', activity.name)))),
    ];
}

function contactsList(contacts) {
    const items = [
        ...contacts.phones.map((phone) => h('a', { href: `tel:${phone}`, class: 'num' }, phone)),
        ...contacts.emails.map((email) => h('a', { href: `mailto:${email}` }, email)),
        contacts.website && h('a', { href: contacts.website, class: 'ext', target: '_blank', rel: 'noopener noreferrer' }, contacts.website.replace(/^https?:\/\//, ''), icon('external')),
    ].filter(Boolean);

    return items.length === 0 ? null : h('ul', { class: 'list-plain' }, items.map((item) => h('li', {}, item)));
}

function taxesText(taxes) {
    return taxes.special_regimes.length > 0 ? taxes.special_regimes.join(', ') : null;
}

function smeText(sme) {
    return sme ? `${capitalize(sme.category.toLowerCase())}${sme.included_at ? `, с ${format.date(sme.included_at)}` : ''}` : 'Не состоит';
}

function companySheets(card) {
    const capital = card.authorized_capital;
    const neighbours = card.address?.mass_registration_count ?? 0;

    return [
        managementSheet(card.managers ?? [], card.managing_organization),
        factsSheet('facts-title', 'Сведения о регистрации', [
            ['Юридический адрес', card.address?.full ? [card.address.full, neighbours > 0 && h('div', { class: 'muted' },
                `По этому адресу зарегистрированы ещё ${counted(neighbours, ['организация', 'организации', 'организаций'])}`)] : null],
            ['Регион', card.region?.name],
            ['Организационно-правовая форма', card.legal_form?.name],
            ['Уставный капитал', capital?.amount != null ? h('span', { class: 'num' }, format.rubles(capital.amount)) : null],
            ['Основной вид деятельности', activities(card.main_activity, card.additional_activities ?? [])],
            ['ОКПО', card.okpo ? h('span', { class: 'num' }, card.okpo) : null],
            ['Особый налоговый режим', taxesText(card.taxes)],
            ['Реестр МСП', smeText(card.sme)],
            ['Контакты', contactsList(card.contacts)],
        ]),
    ];
}

const FINANCE_COLUMNS = ['Год', 'Выручка', 'Чистая прибыль', 'Активы', 'Капитал', 'Рентабельность', 'Рост выручки', 'Долговая нагрузка', 'Отчёт'];

async function renderFinances(container, id, ticket) {
    container.replaceChildren(skeletonSheet(8));
    const { data } = await api(`/v1/companies/${id}/finances`);
    if (ticket !== navigation) return;

    if (dossier?.id === id) dossier.financeYears = data.years.length;
    setInventoryCount('finances', data.years.length);

    const head = h('div', { class: 'sheet-head' }, h('h2', { id: 'finance-title' }, 'Бухгалтерская отчётность'), h('span', { class: 'meta' }, 'млн руб., убыток в скобках'));

    if (!data.has_statements) {
        container.replaceChildren(h('section', { class: 'sheet', 'aria-labelledby': 'finance-title' }, head,
            emptyBlock('Отчётность по общим формам не публикуется', 'Так бывает у банков и страховых компаний: они отчитываются по отраслевым формам, поэтому годовых показателей в общем реестре нет.')));
        return;
    }

    const peak = Math.max(...data.years.map((year) => year.indicators.revenue ?? 0), 1);
    const cell = (value, tone) => h('td', { class: `end num${tone ? ` ${tone}` : ''}`, title: value == null ? 'Нет данных' : null }, value ?? DASH);
    const signTone = (number) => (number != null && number < 0 ? 'neg' : null);

    const rows = data.years.map(({ year, indicators, metrics, report_url: reportUrl }) => h('tr', {},
        h('th', { scope: 'row', class: 'num' }, year),
        h('td', { class: 'end num' }, format.millions(indicators.revenue) ?? DASH, indicators.revenue != null
            && h('span', { class: 'scale', 'aria-hidden': 'true', style: `width: ${((indicators.revenue / peak) * 100).toFixed(1)}%` })),
        cell(format.millions(indicators.net_profit), signTone(indicators.net_profit)),
        cell(format.millions(indicators.total_assets)),
        cell(format.millions(indicators.equity), signTone(indicators.equity)),
        cell(format.percent(metrics.net_margin), signTone(metrics.net_margin)),
        cell(format.percent(metrics.revenue_growth, true), signTone(metrics.revenue_growth)),
        cell(format.percent(metrics.debt_ratio)),
        h('td', {}, reportUrl
            ? h('a', { class: 'ext', href: reportUrl, target: '_blank', rel: 'noopener noreferrer', 'aria-label': `Отчётность за ${year} год на сайте ГИР БО` }, 'PDF', icon('external'))
            : h('span', { class: 'muted' }, DASH))));

    container.replaceChildren(h('section', { class: 'sheet', 'aria-labelledby': 'finance-title' }, head,
        h('div', { class: 'scroll-x' }, h('table', { class: 'table finance' },
            h('thead', {}, h('tr', {}, FINANCE_COLUMNS.map((title, index) => h('th', { scope: 'col', class: index > 0 && index < FINANCE_COLUMNS.length - 1 ? 'end' : null }, title)))),
            h('tbody', {}, rows))),
        h('p', { class: 'sheet-note' }, 'Рентабельность — чистая прибыль к выручке, долговая нагрузка — обязательства к активам. Полоса под выручкой показывает её на общей для всех лет шкале. Показатели посчитаны по итоговым строкам отчётности; прочерк означает, что для расчёта не хватает данных.')));
}

const COLLECTION_TABLES = {
    trademarks: {
        columns: ['№', 'Номер', 'Регистрация', 'Действует до'],
        row: (item) => [
            item.url ? h('a', { class: 'ext num', href: item.url, target: '_blank', rel: 'noopener noreferrer' }, item.id ?? 'Карточка', icon('external')) : h('span', { class: 'num' }, item.id ?? DASH),
            h('span', { class: 'num' }, format.date(item.registered_at) ?? DASH),
            h('span', { class: 'num' }, format.date(item.expires_at) ?? DASH),
        ],
    },
    licenses: {
        columns: ['№', 'Номер', 'Выдана', 'Срок действия', 'Кем выдана', 'Вид деятельности'],
        row: (item) => [
            h('span', { class: 'num' }, item.number ?? DASH),
            h('span', { class: 'num' }, format.date(item.issued_at) ?? DASH),
            h('span', { class: 'num' }, item.valid_until ? `до ${format.date(item.valid_until)}` : 'бессрочно'),
            item.issuer ?? DASH,
            item.activities.length > 0 ? item.activities.join('; ') : DASH,
        ],
    },
    branches: {
        columns: ['№', 'Тип', 'Наименование', 'КПП', 'Адрес'],
        row: (item) => [
            item.kind === 'branch' ? 'Филиал' : 'Представительство',
            format.quotes(item.name) ?? DASH,
            h('span', { class: 'num' }, item.kpp ?? DASH),
            [item.address ?? DASH, item.country && h('div', { class: 'muted' }, item.country)],
        ],
    },
    founders: {
        columns: ['№', 'Учредитель', 'ИНН', 'Доля', 'С какого времени'],
        row: (item) => [
            item.access_restricted ? h('span', { class: 'stamp stamp-sm tone-restricted' }, 'Сведения закрыты') : format.quotes(item.name) ?? DASH,
            h('span', { class: 'num' }, item.inn ?? DASH),
            h('span', { class: 'num' }, item.share?.percent != null ? format.percent(item.share.percent / 100) : item.share?.nominal != null ? format.rubles(item.share.nominal) : DASH),
            h('span', { class: 'num' }, format.date(item.since) ?? DASH),
        ],
    },
};

async function renderCollection(container, id, section, params, ticket) {
    const page = Math.max(1, Number.parseInt(params.get('page') ?? '1', 10) || 1);
    container.replaceChildren(skeletonSheet(10));

    const { data: items, meta } = await api(`/v1/companies/${id}/${section}`, { page, limit: 50 });
    if (ticket !== navigation) return;

    const table = COLLECTION_TABLES[section];
    const pagination = meta.pagination;
    const offset = (pagination.page - 1) * pagination.per_page;

    container.replaceChildren(h('section', { class: 'sheet', 'aria-labelledby': 'collection-title' },
        h('div', { class: 'sheet-head' },
            h('h2', { id: 'collection-title' }, SECTIONS[section]),
            h('span', { class: 'meta num' }, counted(pagination.total ?? items.length, ['запись', 'записи', 'записей']))),
        items.length === 0
            ? emptyBlock('Записей нет', 'В реестре нет сведений этого раздела.')
            : h('div', { class: 'scroll-x' }, h('table', { class: 'table' },
                h('thead', {}, h('tr', {}, table.columns.map((title) => h('th', { scope: 'col' }, title)))),
                h('tbody', {}, items.map((item, index) => h('tr', {},
                    h('td', { class: 'num muted index' }, offset + index + 1),
                    table.row(item).map((content) => h('td', {}, content))))))),
        pager(pagination, (nextPage) => `#/companies/${id}/${section}?page=${nextPage}`)));
}

function entrepreneurCover(result) {
    const card = result.data;

    return h('section', { class: 'sheet', 'aria-labelledby': 'cover-title' },
        h('div', { class: 'cover-head' },
            h('h1', { id: 'cover-title' }, 'Дело №'),
            h('span', { class: 'num' }, card.ogrnip),
            card.extracted_at && h('span', { class: 'meta' }, `Выписка от ${format.date(card.extracted_at)}`)),
        staleNote(result),
        h('dl', { class: 'graphs' },
            graph('Предприниматель', [h('span', { class: 'company-name' }, card.full_name ?? DASH), card.type && h('span', { class: 'company-full' }, card.type)], 'graph-wide'),
            graph('ИНН', h('span', { class: 'num' }, card.inn), 'graph-id'),
            graph('Начато', h('span', { class: 'num' }, format.date(card.registered_at) ?? DASH)),
            graph('Окончено', h('span', { class: 'num' }, format.date(card.termination?.date) ?? DASH)),
            stateGraph(card.status, card.termination, 'entrepreneur')),
        verdict(card));
}

async function renderEntrepreneur({ match, ticket }) {
    const [, id] = match;
    syncField(id);
    setTitle(`Предприниматель ${id}`);
    app.replaceChildren(skeletonDossier());

    const result = await api(`/v1/entrepreneurs/${id}`);
    if (ticket !== navigation) return;

    const card = result.data;
    dossier = { resource: 'entrepreneurs', id, result };
    setTitle(card.full_name);

    app.replaceChildren(h('div', { class: 'dossier' },
        h('div', { class: 'stack' },
            entrepreneurCover(result),
            factsSheet('facts-title', 'Сведения о регистрации', [
                ['Регион', card.region?.name],
                ['Населённый пункт', card.locality],
                ['Основной вид деятельности', activities(card.main_activity, card.additional_activities ?? [])],
                ['ОКПО', card.okpo ? h('span', { class: 'num' }, card.okpo) : null],
                ['Особый налоговый режим', taxesText(card.taxes)],
                ['Реестр МСП', smeText(card.sme)],
                ['Контакты', contactsList(card.contacts)],
            ])),
        inventory(`#/entrepreneurs/${id}`, [
            { key: 'card', title: 'Реквизиты и состояние', count: DASH },
            { key: 'licenses', title: 'Лицензии', count: card.collections?.licenses ?? 0, inert: true },
            { key: 'trademarks', title: 'Товарные знаки', count: card.collections?.trademarks ?? 0, inert: true },
        ], 'card')));
}

function minutesText(seconds) {
    const minutes = Math.max(1, Math.ceil(seconds / 60));
    return minutes >= 60 ? 'через час или позже' : `через ${counted(minutes, ['минуту', 'минуты', 'минут'])}`;
}

function problemSheet(error) {
    const retry = h('button', { class: 'btn', type: 'button', onclick: () => { dossier = null; route(); } }, icon('retry'), 'Повторить');
    let tone = 'warn';
    let title = 'Сбой сервиса';
    let text = error.message;
    const actions = [];

    if (error.status === 401) {
        tone = 'bad';
        title = 'Нет доступа';
        text = 'Токен доступа не подошёл. Укажите действующий токен или верните демо-токен.';
        actions.push(h('button', { class: 'btn btn-primary', type: 'button', onclick: () => openAccess('Токен не подошёл. Проверьте его или верните демо-токен.') }, icon('key'), 'Указать токен'));
    } else if (error.status === 404) {
        title = 'Не найдено';
        text = `${error.message.replace(/\.$/, '')}. Проверьте номер или найдите контрагента по названию.`;
        actions.push(h('button', { class: 'btn', type: 'button', onclick: () => { syncField(''); field.focus(); } }, icon('search'), 'Искать по названию'));
    } else if (error.status === 422) {
        tone = 'bad';
        title = 'Номер не прошёл проверку';
        text = `${error.message.replace(/\.$/, '')}. Скорее всего, в номере опечатка: сверьте его с договором или счётом.`;
        setHint(error.message, true);
    } else if (error.status === 429) {
        title = 'Лимит запросов исчерпан';
        text = 'Лимит этого токена на сегодня исчерпан. Он обновится в полночь по московскому времени.';
    } else if (error.code === 'QUOTA_EXHAUSTED') {
        title = 'Реестр временно недоступен';
        text = 'Дневной лимит обращений к источнику исчерпан. Повторите после полуночи по московскому времени.';
    } else if ([0, 502, 503, 504].includes(error.status)) {
        title = 'Реестр не отвечает';
        text = `${error.message.replace(/\.$/, '')}.${error.retryAfter ? ` Повторите ${minutesText(error.retryAfter)}.` : ''}`;
        actions.push(retry);
    } else {
        actions.push(retry);
    }

    return h('section', { class: `sheet problem tone-${tone}`, role: 'alert' },
        h('div', { class: 'empty' }, h('h2', {}, icon('alert'), title), h('p', {}, text), actions.length > 0 && h('div', { class: 'row' }, actions)));
}

const ROUTES = [
    { pattern: /^\/$/, render: renderStart },
    { pattern: /^\/search$/, render: renderSearch },
    { pattern: /^\/companies\/(\d+)(?:\/(finances|trademarks|licenses|branches|founders))?$/, render: renderCompany },
    { pattern: /^\/entrepreneurs\/(\d+)$/, render: renderEntrepreneur },
];

function currentLocation() {
    const [path, query = ''] = location.hash.replace(/^#/, '').split('?');
    return { path: path || '/', params: new URLSearchParams(query) };
}

async function route() {
    const ticket = ++navigation;
    const { path, params } = currentLocation();
    const target = ROUTES.find((candidate) => candidate.pattern.test(path));

    if (!target) {
        location.replace('#/');
        return;
    }

    const match = path.match(target.pattern);
    const keepsDossier = target.render === renderCompany && dossier?.resource === 'companies' && dossier.id === match[1];

    try {
        await target.render({ match, params, ticket });
    } catch (error) {
        if (ticket !== navigation) return;

        if (error.code === 'WRONG_RESOURCE') {
            const other = target.render === renderCompany ? 'entrepreneurs' : 'companies';
            location.replace(`#/${other}/${match[1]}`);
            return;
        }

        dossier = null;
        const sheet = problemSheet(error);
        setTitle(sheet.querySelector('h2')?.textContent);
        app.replaceChildren(sheet);
    }

    if (ticket !== navigation) return;
    const section = document.getElementById('section');

    if (keepsDossier && section) {
        const top = section.getBoundingClientRect().top;
        if (top < 0 || top > window.innerHeight * 0.6) section.scrollIntoView({ block: 'start' });
    }
    else if (document.activeElement !== field) app.focus({ preventScroll: true });
}

field.addEventListener('input', () => {
    const classified = classify(field.value);
    setHint(classified.hint);
});

lookup.addEventListener('submit', (event) => {
    event.preventDefault();
    const classified = classify(field.value);

    if (classified.kind === 'id') go(`#/${classified.resource}/${classified.id}`);
    else if (classified.kind === 'name') go(`#/search?${new URLSearchParams({ q: classified.query })}`);
    else {
        setHint(classified.hint || 'Введите ИНН, ОГРН или название', true);
        field.focus();
    }
});

access.addEventListener('toggle', (event) => {
    if (event.newState === 'open') {
        tokenInput.value = token.get();
        tokenInput.select();
    }
});

accessForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const value = tokenInput.value.trim();

    if (value && value !== DEMO_TOKEN) token.set(value);
    else token.clear();

    access.hidePopover?.();
    dossier = null;
    route();
});

document.getElementById('token-reset').addEventListener('click', () => {
    token.clear();
    tokenInput.value = DEMO_TOKEN;
    access.hidePopover?.();
    dossier = null;
    route();
});

window.addEventListener('hashchange', route);

fetch('/health').then((response) => showProvenance(response.headers.get('X-Data-Source'))).catch(() => {});
route();
