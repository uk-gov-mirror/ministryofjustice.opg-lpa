Feature: UserDetails

    I want to be able to manage my details

    @focus
    Scenario: Cancel changing my password returns me to previous page (LPAL-210)
        Given I ignore application exceptions

        # standard journey - user goes to "Your details", then "Change password",
        # then clicks cancel
        When I log in as standard test user
        And I visit link containing "Your details"
        Then I am taken to "/user/about-you"
        When I visit link containing "Change Password"
        Then I am taken to "/user/change-password"
        When I click "form-cancel"
        Then I am taken to "/user/about-you"

        # direct to change password - e.g. user cuts and pastes URL into browser
        # for the change password page, goes to page, then clicks cancel
        When I visit "/user/delete"
        And I visit "/user/change-password"
        When I click "form-cancel"
        Then I am taken to "/user/delete"
        When I visit "/user/about-you"
        And I visit link containing "Change Password"
        Then I am taken to "/user/change-password"

        # simulate a page reload/refresh
        When I visit "/user/change-password"

        # back button should still point to the original
        # previous page, not to the change password page we refreshed
        And I click "form-cancel"
        Then I am taken to "/user/about-you"
