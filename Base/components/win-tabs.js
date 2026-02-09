/**
 * get selected: { tab<button>, panel<article> }
 * set selected(tab<button>): void
 * append(label<string>, [content<string>]): { tab<button>, panel<article> }
 * 
 * siendo:
 * tab: tabs > menu > button[role="tab"]
 * panel: tabs > article[role="tabpanel"]
 */
export class WinTabs extends IrbisElement {
    get tabs() {
        return this.element.querySelectorAll(':scope > menu > button[role="tab"]');
    }

    get panels() {
        return this.element.querySelectorAll(':scope > article[role="tabpanel"]');
    }

    get selected() {
        const tabs = this.tabs;
        const panels = this.panels;

        const selectedTab = this.element.querySelector(':scope > menu > button[aria-selected]');
        const selectedIndex = Array.from(tabs).indexOf(selectedTab);
        const selectedPanel = panels[selectedIndex];

        return {
            tab: selectedTab,
            panel: selectedPanel
        }
    }

    set selected(tab) {
        const selected = this.selected;
        if (tab === selected.tab) return;

        const tabs = this.tabs;
        const panels = this.panels;

        const selectedTab = tab;
        const selectedIndex = Array.from(tabs).indexOf(tab);
        const selectedPanel = panels[selectedIndex];
        
        selected.tab.removeAttribute('aria-selected');
        selected.panel.setAttribute('hidden', true);

        selectedTab.setAttribute('aria-selected', 'true');
        selectedPanel.removeAttribute('hidden');
    }

    get selectedIndex() {
        const tabs = this.tabs;
        const selected = this.selected;
        return Array.from(tabs).indexOf(selected.tab);
    }

    set selectedIndex(index) {
        const tabs = this.tabs;
        if (index < 0 || index >= tabs.length) return;
        this.selected = tabs[index];
    }

    constructor (element) {
        super(element);
        this.tabs.forEach((tab) => {
            tab.addEventListener('click', (event) => {
                this.selected = event.currentTarget;
            });
        });
    }

    append (label, content) {
        content = content || '';

        const tab = Element.create('button', {
            attributes: {
                'role': 'tab', 
                'aria-controls': 'tab_'+label
            },
            textContent: label,
            events: {
                click: (ev) => {
                    this.selected = ev.currentTarget;
                }
            }
        });

        const panel = Element.create('article', {
            attributes: {
                'role': 'tabpanel', 
                'hidden': true,
                'id': 'tab_'+label
            },
            innerHTML: content
        });

        this.element.querySelector(':scope > menu').append(tab);
        this.element.append(panel);
        return {
            tab: tab,
            panel: panel
        }
    }

    remove (tab) {
        const selected = this.selected;
        if (tab === selected.tab) {
            this.selected = this.tabs[this.selectedIndex - 1] || this.tabs[0];
        }
        const tabs = this.tabs;
        const panels = this.panels;

        const index = Array.from(tabs).indexOf(tab);
        const panel = panels[index];
        tab.remove();
        panel.remove();
    }
}