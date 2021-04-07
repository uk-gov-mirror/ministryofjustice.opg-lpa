import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I can get {string} from link containing {string}`, (fileName, linkText) => {
    cy.contains(linkText)
     .should('have.attr', 'href')
     .then((href) => {
         for (var i = 0 ; i <3000 ; i++) {
              cy.request({
                  url: href,
                  timeout: 20000,
                })
              .then((response) => {
                  expect(response).to.have.property('headers')
                  expect(response.body).to.contain('meta http-equiv="refresh" content="2"')
                  expect(response.body).to.not.contain('meta http-equiv="refresh" content="2; url=')
                  expect(response.headers['content-type']).to.contain('text/html')
                  expect(response.body).to.have.length.gt(500)
                  })
              cy.wait(10000);
         }
    })
})
