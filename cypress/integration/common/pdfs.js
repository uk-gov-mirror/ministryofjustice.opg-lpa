import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can get {string} from link containing {string}`, (fileName, linkText) => {
    var body = "";
    var i = 0;
    cy.wrap(body).as('not yet set');
    cy.contains(linkText)
     .should('have.attr', 'href')
     .then((href) => {
         //while ((i < 100) && !(body.includes('meta http-equiv="refresh" content="2; url='))) {
         while ((i < 100) && !(body.includes('meta http-equiv="refresh" content="2; url='))) {
              cy.request({
                  url: href
                })
              .then((response) => {
                  expect(response).to.have.property('headers')
                  //expect(response.body).to.contain('meta http-equiv="refresh" content="2"')
                  //expect(response.body).to.not.contain('meta http-equiv="refresh" content="2; url=')
                  expect(response.headers['content-type']).to.contain('text/html')
                  expect(response.body).to.have.length.gt(500)
                  cy.wrap(body).as(response.body);
                  body = response.body;
                  if (body.includes('meta http-equiv="refresh" content="2; url=')) {
                      return cypress.Promise
                  }
                  })
              cy.wait(10000);
              i++;
         }
         cy.wait('@body').then(() =>
         {
             cy.log("BODY !!! GOT HERE !!!");
         })
        //cy.log("GOT HERE !!!");
        //assert(body.includes('meta http-equiv="refresh" content="2; url='));
    })
})
