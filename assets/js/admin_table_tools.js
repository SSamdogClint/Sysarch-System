/*
  assets/js/admin_table_tools.js
  Reusable table tools for admin pages:
  - Global search
  - Sort by clicking headers
  - Choose number of entries
  - Pagination

  Usage:
  Add class="js-admin-table" to any table.
*/
(function () {
  function normalize(text) {
    return (text || '').toString().trim().toLowerCase();
  }

  function createEl(tag, className, text) {
    const el = document.createElement(tag);

    if (className) {
      el.className = className;
    }

    if (text !== undefined) {
      el.textContent = text;
    }

    return el;
  }

  function getCellValue(row, index) {
    const cell = row.cells[index];

    if (!cell) {
      return '';
    }

    return cell.textContent.trim();
  }

  function compareValues(a, b, direction) {
    const numberA = parseFloat(a.replace(/[^0-9.-]/g, ''));
    const numberB = parseFloat(b.replace(/[^0-9.-]/g, ''));

    const bothNumbers = !Number.isNaN(numberA) && !Number.isNaN(numberB) && /[0-9]/.test(a) && /[0-9]/.test(b);

    let result;

    if (bothNumbers) {
      result = numberA - numberB;
    } else {
      result = a.localeCompare(b, undefined, {
        numeric: true,
        sensitivity: 'base'
      });
    }

    return direction === 'asc' ? result : -result;
  }

  function initTable(table, tableIndex) {
    if (table.dataset.toolsReady === 'true') {
      return;
    }

    const tbody = table.tBodies[0];

    if (!tbody) {
      return;
    }

    const originalRows = Array.from(tbody.rows);

    if (originalRows.length === 0) {
      return;
    }

    table.dataset.toolsReady = 'true';
    table.classList.add('admin-enhanced-table');

    let rows = originalRows.slice();
    let filteredRows = rows.slice();
    let currentPage = 1;
    let pageSize = 10;
    let sortIndex = null;
    let sortDirection = 'asc';
    let searchValue = '';

    const wrapper = createEl('div', 'admin-table-tools-wrapper');
    const controls = createEl('div', 'admin-table-controls');

    const leftControls = createEl('div', 'admin-table-control-left');
    const rightControls = createEl('div', 'admin-table-control-right');

    const entriesLabel = createEl('label', 'admin-table-label');
    entriesLabel.innerHTML = `
      Show
      <select class="admin-table-select" aria-label="Choose number of entries">
        <option value="5">5</option>
        <option value="10" selected>10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="all">All</option>
      </select>
      entries
    `;

    const searchLabel = createEl('label', 'admin-table-label');
    searchLabel.innerHTML = `
      Search:
      <input type="search" class="admin-table-search" placeholder="Type to filter..." aria-label="Search table">
    `;

    leftControls.appendChild(entriesLabel);
    rightControls.appendChild(searchLabel);

    controls.appendChild(leftControls);
    controls.appendChild(rightControls);

    const footer = createEl('div', 'admin-table-footer');
    const info = createEl('div', 'admin-table-info');
    const pagination = createEl('div', 'admin-table-pagination');

    footer.appendChild(info);
    footer.appendChild(pagination);

    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(controls);
    wrapper.appendChild(table);
    wrapper.appendChild(footer);

    const pageSizeSelect = controls.querySelector('.admin-table-select');
    const searchInput = controls.querySelector('.admin-table-search');

    function updateHeaderSortClasses() {
      const headers = table.tHead ? Array.from(table.tHead.rows[0].cells) : [];

      headers.forEach((header, index) => {
        header.classList.remove('sort-asc', 'sort-desc');

        if (index === sortIndex) {
          header.classList.add(sortDirection === 'asc' ? 'sort-asc' : 'sort-desc');
        }
      });
    }

    function applySearchAndSort() {
      const query = normalize(searchValue);

      filteredRows = rows.filter((row) => {
        if (!query) {
          return true;
        }

        return normalize(row.textContent).includes(query);
      });

      if (sortIndex !== null) {
        filteredRows.sort((rowA, rowB) => {
          return compareValues(
            getCellValue(rowA, sortIndex),
            getCellValue(rowB, sortIndex),
            sortDirection
          );
        });
      }

      updateHeaderSortClasses();
    }

    function renderPagination(totalPages) {
      pagination.innerHTML = '';

      if (totalPages <= 1) {
        return;
      }

      const prevBtn = createEl('button', 'admin-page-btn', 'Prev');
      prevBtn.type = 'button';
      prevBtn.disabled = currentPage === 1;
      prevBtn.addEventListener('click', () => {
        currentPage = Math.max(1, currentPage - 1);
        render();
      });

      pagination.appendChild(prevBtn);

      const maxButtons = 5;
      let start = Math.max(1, currentPage - Math.floor(maxButtons / 2));
      let end = Math.min(totalPages, start + maxButtons - 1);

      if (end - start + 1 < maxButtons) {
        start = Math.max(1, end - maxButtons + 1);
      }

      for (let page = start; page <= end; page++) {
        const btn = createEl('button', 'admin-page-btn', String(page));
        btn.type = 'button';

        if (page === currentPage) {
          btn.classList.add('active');
        }

        btn.addEventListener('click', () => {
          currentPage = page;
          render();
        });

        pagination.appendChild(btn);
      }

      const nextBtn = createEl('button', 'admin-page-btn', 'Next');
      nextBtn.type = 'button';
      nextBtn.disabled = currentPage === totalPages;
      nextBtn.addEventListener('click', () => {
        currentPage = Math.min(totalPages, currentPage + 1);
        render();
      });

      pagination.appendChild(nextBtn);
    }

    function render() {
      applySearchAndSort();

      const totalRows = filteredRows.length;
      const useAll = pageSize === 'all';
      const numericPageSize = useAll ? totalRows || 1 : Number(pageSize);
      const totalPages = useAll ? 1 : Math.max(1, Math.ceil(totalRows / numericPageSize));

      if (currentPage > totalPages) {
        currentPage = totalPages;
      }

      const startIndex = useAll ? 0 : (currentPage - 1) * numericPageSize;
      const endIndex = useAll ? totalRows : startIndex + numericPageSize;
      const visibleRows = filteredRows.slice(startIndex, endIndex);

      tbody.innerHTML = '';

      if (visibleRows.length === 0) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        const columnCount = table.tHead && table.tHead.rows.length
          ? table.tHead.rows[0].cells.length
          : 1;

        cell.colSpan = columnCount;
        cell.className = 'admin-table-empty';
        cell.textContent = 'No matching records found.';
        row.appendChild(cell);
        tbody.appendChild(row);
      } else {
        visibleRows.forEach((row) => {
          tbody.appendChild(row);
        });
      }

      const showingStart = totalRows === 0 ? 0 : startIndex + 1;
      const showingEnd = Math.min(endIndex, totalRows);

      info.textContent = `Showing ${showingStart} to ${showingEnd} of ${totalRows} entries`;

      if (searchValue) {
        info.textContent += ` (filtered from ${rows.length} total entries)`;
      }

      renderPagination(totalPages);
    }

    if (table.tHead && table.tHead.rows.length) {
      Array.from(table.tHead.rows[0].cells).forEach((header, index) => {
        header.classList.add('admin-sortable-header');
        header.setAttribute('role', 'button');
        header.setAttribute('tabindex', '0');

        function triggerSort() {
          if (sortIndex === index) {
            sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
          } else {
            sortIndex = index;
            sortDirection = 'asc';
          }

          currentPage = 1;
          render();
        }

        header.addEventListener('click', triggerSort);
        header.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            triggerSort();
          }
        });
      });
    }

    pageSizeSelect.addEventListener('change', () => {
      pageSize = pageSizeSelect.value === 'all' ? 'all' : Number(pageSizeSelect.value);
      currentPage = 1;
      render();
    });

    searchInput.addEventListener('input', () => {
      searchValue = searchInput.value;
      currentPage = 1;
      render();
    });

    render();
  }

  function initAdminTableTools() {
    document.querySelectorAll('table.js-admin-table').forEach((table, index) => {
      initTable(table, index);
    });
  }

  window.initAdminTableTools = initAdminTableTools;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminTableTools);
  } else {
    initAdminTableTools();
  }
})();
