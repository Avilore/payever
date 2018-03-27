Feature:

  Scenario: User can get redirect usl
    Given I have the payload:
    """
  "client_id": "30801_44jezpd83a68wc4k8c8wsssco4k0w0gow4owswoc0g0oksc8o8",
  "client_secret" : "9vejpbzp8k08sk0k08cg00ocsgco80ckog8s800kgwcckwss0",
  "grant_type" : "http://www.payever.de/api/payment",
  "scope" : "API_CREATE_PAYMENT"
  """
    And I set the "<Content-Type>" header to be "<application/json>"
    When I request "POST /oauth/v2/token"
    Then the response status code should be 200
    And I get oauth token in response property "<access_token>"
    And I have the payload:
    """
 "channel" : "other_shopsystem",
  "amount" : "100.00",
  "order_id" : "900001291100",
  "currency" : "USD"
  """
    And I set the "<Content-Type>" header to be "<application/x-www-form-urlencoded>"
    And I set the "<Authorization>" header with oauth_token
    When I request "POST /api/payment"
    Then the response status code should be 200
    And I get redirect url in response property "<redirect url>"

  Scenario: User can proceed to payment
    Given I am on "/pay/load-api/87fd3fa3732cc8ae24fafe4f601ace72"
    When I fill in the following:
      | email   | iaroslava.goniukova@gmail.com |
      | address | Gran Vía, 43, Madrid, Spain   |
    And I press "Continue"
    Then I should see "Billing and shipping address"

    Scenario: Success message is shown
      When I am on "/pay/03b7e0325cbf6b2c253142d0e0547348/shipping/billing"
      And I press "Continue-Payment method"
      Then I should see text matching "Success payment"

      
