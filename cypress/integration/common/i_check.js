import { Then } from "cypress-cucumber-preprocessor/steps";
 
Then(`I check {string}`, (checkable) => {
    cy.get("[data-cy=" + checkable + "]").check();
})
 
Then(`I check occurrence {int} of radio button`, (seq) => {
    cy.get('[type="radio"]').eq(seq).check()
})
Then(`I check occurrence {int} of checkbox`, (seq) => {
    cy.get('[type="checkbox"]').eq(seq).check()
})
