/* HCJM Roster — Public JS (vanilla, no dependencies) */

(function () {
    'use strict';

    /**
     * Position filter tabs on the roster.
     * Adds filter buttons above the grid if there are multiple positions.
     */
    function initRosterFilter() {
        var grids = document.querySelectorAll('.hcjm-roster .hcjm-cards');
        if (!grids.length) return;

        grids.forEach(function (grid) {
            var cards = grid.querySelectorAll('.hcjm-player-card');
            if (cards.length < 4) return;

            var positions = {};
            cards.forEach(function (card) {
                var pos = card.getAttribute('data-position') || '';
                if (pos) positions[pos] = true;
            });

            var posKeys = Object.keys(positions);
            if (posKeys.length < 2) return;

            var posLabels = {
                'brankár':  'Brankáři',
                'obrance':  'Obránci',
                'utocnik':  'Útočníci',
            };

            var filterBar = document.createElement('div');
            filterBar.className = 'hcjm-filter-bar';

            var allBtn = createFilterBtn('Všichni', 'all', true);
            filterBar.appendChild(allBtn);

            posKeys.forEach(function (pos) {
                var btn = createFilterBtn(posLabels[pos] || pos, pos, false);
                filterBar.appendChild(btn);
            });

            grid.parentNode.insertBefore(filterBar, grid);

            filterBar.addEventListener('click', function (e) {
                var btn = e.target.closest('.hcjm-filter-btn');
                if (!btn) return;

                filterBar.querySelectorAll('.hcjm-filter-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                var filter = btn.getAttribute('data-filter');
                cards.forEach(function (card) {
                    if (filter === 'all' || card.getAttribute('data-position') === filter) {
                        card.hidden = false;
                    } else {
                        card.hidden = true;
                    }
                });
            });
        });
    }

    function createFilterBtn(label, filter, active) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'hcjm-filter-btn' + (active ? ' active' : '');
        btn.setAttribute('data-filter', filter);
        btn.textContent = label;
        return btn;
    }

    document.addEventListener('DOMContentLoaded', function () {
        initRosterFilter();
    });
})();
