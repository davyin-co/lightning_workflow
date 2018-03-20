@api @lightning_workflow @javascript
Feature: Scheduling transitions on content

  Background:
    Given I am logged in as a user with the "create page content, view own unpublished content, edit own page content, use editorial transition create_new_draft, use editorial transition publish, use editorial transition archive, use editorial transition archived_draft, schedule editorial transition publish, schedule editorial transition archive, view latest version" permissions
    And I visit "/node/add/page"
    And I enter "Schedule This" for "Title"

  @55c3c017
  Scenario: Automatically publishing in the future
    When I click "Schedule a transition"
    And I select "Published" from "Scheduled moderation state"
    And I set "Scheduled transition time" to 10 seconds from now
    And I click "Save transition"
    And I press "Save"
    And I wait 15 seconds
    And I run cron
    And I visit the edit form
    Then I should see "Current state Published"

  @368f0045
  Scenario: Automatically publishing, then unpublishing, in the future
    When I click "Schedule a transition"
    And I select "Published" from "Scheduled moderation state"
    And I set "Scheduled transition time" to 10 seconds from now
    And I click "Save transition"
    And I click "add another"
    And I select "Archived" from "Scheduled moderation state"
    And I set "Scheduled transition time" to 20 seconds from now
    And I click "Save transition"
    And I press "Save"
    And I wait 15 seconds
    And I run cron
    And I wait 10 seconds
    And I run cron
    And I visit the edit form
    Then I should see "Current state Archived"

  @4e8a6957
  Scenario: Automatically publishing when there is a pending revision
    When I select "Published" from "moderation_state[0][state]"
    And I press "Save"
    And I visit the edit form
    And I enter "MC Hammer" for "Title"
    And I select "Draft" from "moderation_state[0][state]"
    And I click "Schedule a transition"
    And I select "Published" from "Scheduled moderation state"
    And I set "Scheduled transition time" to 10 seconds from now
    And I click "Save transition"
    And I press "Save"
    And I wait 15 seconds
    And I run cron
    And I click "View"
    Then I should see "MC Hammer"
