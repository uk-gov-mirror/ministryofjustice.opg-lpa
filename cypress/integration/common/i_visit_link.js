import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I visit link named {string}`, (linkName) => {
    cy.get(linkName).click();
    cy.OPGCheckA11y();
})

Then(`I visit link containing {string}`, (linkText) => {
    cy.contains(linkText).click();
    cy.OPGCheckA11y();
})

// Test whether we can visit this link in a new tab. First we ensure there
// is indeed a target _blank, thats enough to say this would open in a new tab
// then remove that before clicking it. Since Cypress doesn't support
// multiple tabs, we then visit this link in the parent window
//  clearly, this is not 100% the same as the user journey, extra testing
//  may need doing by hand on some pages that open in tabs

Then(`I visit link in new tab containing {string}`, (linkText) => {
    cy.contains(linkText).should('have.attr', 'target', '_blank').invoke('removeAttr', 'target').click();
    cy.OPGCheckA11y();
})

Then(`I visit in new tab link named {string}`, (linkName) => {
    cy.get(linkName).should('have.attr', 'target', '_blank').invoke('removeAttr', 'target').click();
    cy.OPGCheckA11y();
})

Then(`I request link in new tab containing {string}`, (linkText) => {
    cy.contains(linkText)
     .should('have.attr', 'href')
     .then((href) => {
         //doRequest(href)
         for (var i = 0 ; i <3000 ; i++) {
              doRequest(href);
              cy.wait(10000);
         }
    })
})

function doRequest(href) {
      cy.request({
          url: href,
          timeout: 20000,
        })
      .then((response) => {
          expect(response).to.have.property('headers')
          //cy.log(Object.keys(response.headers));
          cy.log(response.headers['http-equiv']);
          //expect(response.headers['meta http-equiv']).to.contain('text/html')
          expect(response.body).to.contain('meta http-equiv="refresh" content="2"')
          expect(response.body).to.not.contain('meta http-equiv="refresh" content="2; url=')
          expect(response.headers['content-type']).to.contain('text/html')
          /*for (var i = 0; i < response.headers.length; i++) {
                cy.log( response.headers[i] ); // writes first names to console
          }*/
          //expect(response.status).to.eq(200)
          expect(response.body).to.have.length.gt(500)
          })
}

