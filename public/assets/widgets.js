$(function () {
    window.searcher = new function () {
        let self = this;

        this.getRefinementListHeader = (name) => {
            return '<legend class="col-form-label-lg pt-0">' + util.capitalizeFirstLetter(name) + ' :</legend>'
        }
        this.getRefinementListItem = (data) => {
            return ' <div class="form-check">\n' +
                '        <label class="form-check-label" for="crli' + data.value + '">' +
                '        <input class="form-check-input" type="checkbox" value="' + data.value + '" id="crli' + data.value + '" ' + (data.isRefined ? 'checked' : '') + '>\n' +
                util.capitalizeFirstLetter(data.label) + '</label>' +
                '</div>'
        }
        this.getRefinementList = (name) => {
            return instantsearch.widgets.refinementList({
                container: '#' + name + '-refinement-list',
                attributeName: name,
                sortBy: ['name:asc'],
                templates: {
                    header: self.getRefinementListHeader(name),
                    item: self.getRefinementListItem
                }
            })
        }

        this.getHierarchicalMenuHeader = (name) => {
            return '<legend class="col-form-label-lg pt-0">' + _.capitalize(name) + ' :</legend>'
        }
        this.getHierarchicalMenuItem = (data) => {
            return '<a href="' + data.url + '" class="facet-item ' + (data.isRefined ? 'active' : '') + '">' +
                '<span class="facet-name"><i class="fa fa-angle-right"></i> > ' + _.capitalize(data.label) + ' </span class="facet-name"></a>';
        }
        this.getHierarchicalMenu = (name) => {
            return instantsearch.widgets.hierarchicalMenu({
                container: '#hierarchical-' + name,
                attributes: ['hierarchical_' + name + '.lvl0', 'hierarchical_' + name + '.lvl1'],
                separator: '>',
                sortBy: ['name:asc'],
                templates: {
                    header: self.getHierarchicalMenuHeader(name),
                    item: self.getHierarchicalMenuItem
                }
            })
        }
        this.getHitsItemDoc = (data) => {
            // console.log(data);
            return '<div class="hit"><div class="hit-content"><a href="' + data.link + '">' +
                // '<p class="hit-name">' + data._highlightResult.name.value + '</p></a> ' +
                // '<p class="hit-description">' + data._highlightResult.content + '</p>' +
                '</div></div>';
        }
        this.getHitsItemApi = (data) => {
            console.log(data);
            return '<div class="hit"><div class="hit-content"><a href="' + data.link + '">' +
                '<p class="hit-name">' + data._highlightResult.name.value + '</p></a> ' +
                // '<p class="hit-description">' + data._highlightResult.content + '</p>' +
                '</div></div>';
        }
        this.getHits = (name) => {
            return instantsearch.widgets.hits({
                container: '#hits_' + name,
                escapeHits: true,
                templates: {
                    item: self['getHitsItem' + _.capitalize(name)],
                    empty: "We didn't find any results for the search <em>\"{{query}}\"</em>"
                }
            })
        }

        this.getPagination = (name) => {
            return instantsearch.widgets.pagination({
                container: '#pagination_' + name,
                cssClasses: {
                    root: 'pagination',
                    item: 'page-item',
                    link: 'page-link',
                    active: 'active'
                }
            })
        }

        // this.hitsPerPageConnector =  instantsearch.connectors.connectHitsPerPage(
        //         function renderFn (renderOpts, isFirstRendering) {
        //             console.log(renderOpts);
        //             console.log(isFirstRendering);
        //         }
        //     )
        //

        // this.getMenu = (name) => {
        //     return  instantsearch.widgets.menu({
        //         container: '#' + name,
        //         attributeName: name,
        //         limit: 10,
        //         templates: {
        //             header: 'Categories'
        //         }
        //     })
        // }

        this.searchApi = instantsearch({
                appId: 'JIIVBNDTOY',
                apiKey: 'f774ad24b1c6e4b3c1052a7b7738577a',
                indexName: 'objective_php_api',
                urlSync: true,
                searchFunction: (helper) => {
                    //Synchronize the query
                    self.searchDoc.helper.setQuery(self.searchApi.helper.state.query)
                    //Synchronize the disjunctiveFacets
                    self.searchDoc.helper.state.disjunctiveFacets = self.searchApi.helper.state.disjunctiveFacets;
                    self.searchDoc.helper.state.disjunctiveFacetsRefinements = self.searchApi.helper.state.disjunctiveFacetsRefinements;
                    //Synchronize the hierarchicalFacets
                    self.searchDoc.helper.state.hierarchicalFacets = self.searchApi.helper.state.hierarchicalFacets;
                    self.searchDoc.helper.state.hierarchicalFacetsRefinements = self.searchApi.helper.state.hierarchicalFacetsRefinements;
                    //Set hitsPerPage
                    helper.setQueryParameter('hitsPerPage', $('#select-doc').data('hitsPerPage'))
                    self.searchDoc.helper.setQueryParameter('hitsPerPage', $('#select-doc').data('hitsPerPage'))

                    helper.search();
                    self.searchDoc.helper.search();
                },
                searchParameters: {}
            }
        )


        this.searchApi.addWidgets([
            instantsearch.widgets.searchBox({
                container: '#search-input',
                placeholder: 'Search in the doc',
                poweredBy: false,
                magnifier: false,
                reset: false

            }),
            this.getHits('api'),
            this.getPagination('api'),
            instantsearch.widgets.clearAll({
                container: '#clear-all',
                templates: {
                    link: '<button type="button" class="btn btn-primary btn-sm">Reset everything</button>'
                },
                autoHideContainer: false,
                clearsQuery: true,
            }),
            // this.getRefinementList('component'),
            // this.getRefinementList('version'),
            this.getHierarchicalMenu('versions')
        ]);

// this.searchApi.addWidget(this.hitsPerPageConnector())

        this.searchDoc = instantsearch({
            appId: 'JIIVBNDTOY',
            apiKey: 'f774ad24b1c6e4b3c1052a7b7738577a',
            indexName: 'objective_php_doc',
            searchParameters: {
                hitsPerPage: 4
            }
        });

        this.searchDoc.addWidgets([
            this.getHits('doc'),
            this.getPagination('doc')
        ]);


        this.searchDoc.start();
        this.searchApi.start();

// console.log(search.helper);
// console.log(searchDoc.helper);

    }
    ()
})
;