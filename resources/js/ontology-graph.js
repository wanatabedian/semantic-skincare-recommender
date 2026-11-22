// resources/js/ontology-graph.js
import cytoscape from 'cytoscape';

// Colour mapping for the eight ontology classes
const classColors = {
    Product: '#2ca02c',
    Brand: '#1f77b4',
    Category: '#ff7f0e',
    ProductSeries: '#d62728',
    AgeGroup: '#9467bd',
    SkinType: '#8c564b',
    SkinConcern: '#e377c2',
    Ingredient: '#7f7f7f',
};

const container = document.getElementById('knowledge-graph');
if (!container) {
    console.warn('Knowledge graph container not found');
}

// UI elements
const loadBtn = document.getElementById('loadGraph');
const resetBtn = document.getElementById('resetGraph');
const clearBtn = document.getElementById('clearGraph');
const searchInp = document.getElementById('graph-search');
const filterSel = document.getElementById('graph-filter');
const productSel = document.getElementById('graph-product');
const infoPanel = document.getElementById('graph-info');
const graphUrl = container?.dataset.graphUrl || '/ontologi/graph';

let cy = null; // Cytoscape instance

const coseLayoutOptions = {
    name: 'cose',
    animate: true,
    animationDuration: 800,
    fit: true,
    padding: 40,
    randomize: true,
    componentSpacing: 100,
    nodeRepulsion: function () {
        return 204800;
    },
    nodeOverlap: 20,
    idealEdgeLength: function () {
        return 80;
    },
    edgeElasticity: function () {
        return 100;
    },
    nestingFactor: 5,
    gravity: 0.25,
    numIter: 1000,
    initialTemp: 200,
    coolingFactor: 0.95,
    minTemp: 1.0,
};

// Graf satu produk: produk di tengah, tetangga melingkar di sekelilingnya.
const productLayoutOptions = {
    name: 'concentric',
    animate: true,
    animationDuration: 600,
    fit: true,
    padding: 40,
    minNodeSpacing: 30,
    concentric: (node) => (node.data('focus') ? 2 : 1),
    levelWidth: () => 1,
};

function applyStyle() {
    if (!cy) return;
    cy.style()
        .selector('node')
        .style({
            'background-color': (ele) => classColors[ele.data('class')] || '#94a3b8',
            label: (ele) => {
                const l = ele.data('label') || '';
                return l.length > 18 ? l.substring(0, 16) + '…' : l;
            },
            color: '#1e293b',
            'font-size': '9px',
            'font-weight': '600',
            'text-valign': 'bottom',
            'text-halign': 'center',
            'text-margin-y': 4,
            'text-wrap': 'ellipsis',
            'text-max-width': '75px',
            'text-outline-color': '#ffffff',
            'text-outline-width': 2,
            'text-outline-opacity': 0.95,
            width: '26px',
            height: '26px',
            'border-width': 1.5,
            'border-color': '#ffffff',
            'border-opacity': 0.8,
        })
        .selector('node.search-match')
        .style({
            'border-color': '#f59e0b',
            'border-width': 3,
            'border-opacity': 1,
            color: '#b45309',
            'font-weight': 'bold',
            'font-size': '10px',
        })
        .selector('node[?focus]')
        .style({
            width: '40px',
            height: '40px',
            'border-color': '#14532d',
            'border-width': 3,
            'font-size': '11px',
            'text-max-width': '140px',
            label: 'data(label)',
        })
        .selector('edge')
        .style({
            width: 1.5,
            'line-color': '#cbd5e1',
            'target-arrow-color': '#94a3b8',
            'target-arrow-shape': 'triangle',
            'arrow-scale': 0.8,
            label: 'data(label)',
            'font-size': '7px',
            color: '#475569',
            'text-rotation': 'autorotate',
            'curve-style': 'bezier',
            'text-background-color': '#ffffff',
            'text-background-opacity': 0.85,
            'text-background-padding': '2px',
            'text-background-shape': 'roundrectangle',
            opacity: 0.85,
        })
        .update();
}

async function loadGraph() {
    if (!container) return;

    // Loading state
    container.innerHTML = `
        <div class="flex items-center justify-center h-full text-indigo-600 text-sm font-medium">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Memuat graf pengetahuan...</span>
        </div>
    `;

    hideInfo();
    const productId = productSel?.value ?? '';
    const url = productId ? `${graphUrl}?product=${encodeURIComponent(productId)}` : graphUrl;

    try {
        const resp = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!resp.ok) {
            let message = `HTTP ${resp.status}`;
            try {
                message = (await resp.json()).message || message;
            } catch {
                // biarkan pesan HTTP
            }
            showMessage(message, 'text-red-600');
            return;
        }
        const data = await resp.json();

        if (!data.nodes || data.nodes.length === 0) {
            container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 text-sm">Tidak ada data graf yang ditemukan.</div>';
            return;
        }

        container.innerHTML = '';
        if (cy) cy.destroy();

        cy = cytoscape({
            container,
            elements: { nodes: data.nodes, edges: data.edges },
            layout: data.mode === 'product' ? productLayoutOptions : coseLayoutOptions,
            wheelSensitivity: 0.2,
        });

        applyStyle();

        cy.on('layoutstop', () => {
            cy.center();
            cy.fit(undefined, 40);
        });

        cy.on('tap', 'node', (evt) => showInfo(evt.target));

        if (filterSel && filterSel.value) {
            filterByClass();
        }
        if (searchInp && searchInp.value) {
            searchNodes();
        }
    } catch (e) {
        console.error('Failed to load knowledge graph:', e);
        container.innerHTML = '<div class="flex items-center justify-center h-full text-red-600 text-sm">Gagal memuat graf pengetahuan. Silakan coba lagi.</div>';
    }
}

// Pesan teks di dalam kontainer graf (textContent supaya aman dari HTML).
function showMessage(text, colorClass) {
    if (cy) {
        cy.destroy();
        cy = null;
    }
    const div = document.createElement('div');
    div.className = `flex items-center justify-center h-full text-sm ${colorClass}`;
    div.textContent = text;
    container.replaceChildren(div);
}

function hideInfo() {
    if (infoPanel) infoPanel.classList.add('hidden');
}

// Panel informasi node yang diklik.
function showInfo(node) {
    if (!infoPanel) {
        alert(`IRI: ${node.id()}\nLabel: ${node.data('label')}\nClass: ${node.data('class')}`);
        return;
    }
    const field = (name) => infoPanel.querySelector(`[data-info="${name}"]`);
    field('label').textContent = node.data('label') || node.id();
    field('class').textContent = node.data('class') || '-';
    field('degree').textContent = String(node.connectedEdges().length);
    field('iri').textContent = node.id();

    const link = field('url');
    if (node.data('url')) {
        link.href = node.data('url');
        link.style.display = '';
    } else {
        link.style.display = 'none';
    }

    // Untuk produk di mode ringkasan: tombol untuk membuka graf produk itu.
    const focusBtn = field('focus');
    const productId = node.id().split('#').pop();
    const canFocus = node.data('class') === 'Product' && !node.data('focus') && productSel
        && Array.from(productSel.options).some((o) => o.value === productId);
    focusBtn.style.display = canFocus ? '' : 'none';
    focusBtn.onclick = canFocus
        ? () => {
              productSel.value = productId;
              loadGraph();
          }
        : null;

    infoPanel.classList.remove('hidden');
}

function resetView() {
    if (cy) {
        cy.center();
        cy.fit(undefined, 40);
    }
}

function clearGraph() {
    if (cy) {
        cy.elements().remove();
        cy.destroy();
        cy = null;
    }
    if (container) {
        container.innerHTML = '<div class="flex items-center justify-center h-full text-gray-400 text-sm">Graf kosong. Klik "Muat Graf" untuk memuat visualisasi.</div>';
    }
    if (searchInp) searchInp.value = '';
    if (filterSel) filterSel.value = '';
    hideInfo();
}

function searchNodes() {
    if (!cy) return;
    const term = (searchInp?.value ?? '').trim().toLowerCase();
    cy.nodes().removeClass('search-match');
    if (!term) return;
    cy.nodes().filter((ele) => (ele.data('label') || '').toLowerCase().includes(term)).addClass('search-match');
}

function filterByClass() {
    if (!cy) return;
    const cls = filterSel?.value;
    cy.nodes().style('display', (ele) => (cls && ele.data('class') !== cls ? 'none' : 'element'));
    cy.edges().style('display', (ele) => {
        const src = cy.getElementById(ele.data('source'));
        const tgt = cy.getElementById(ele.data('target'));
        return src.visible() && tgt.visible() ? 'element' : 'none';
    });
}

// Window resize handler for responsiveness
window.addEventListener('resize', () => {
    if (cy) {
        cy.resize();
        cy.fit(undefined, 40);
    }
});

// Event listeners
if (loadBtn) loadBtn.addEventListener('click', loadGraph);
if (resetBtn) resetBtn.addEventListener('click', resetView);
if (clearBtn) clearBtn.addEventListener('click', clearGraph);
if (searchInp) searchInp.addEventListener('input', searchNodes);
if (filterSel) filterSel.addEventListener('change', filterByClass);
if (productSel) productSel.addEventListener('change', loadGraph);
