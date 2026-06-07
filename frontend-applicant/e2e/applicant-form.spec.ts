import { expect, test, type Page } from '@playwright/test'

const errorAlert = (page: Page) => page.getByRole('alert')

async function waitForFormReady(page: Page) {
  await Promise.all([
    page.waitForResponse((resp) => resp.url().includes('/api/skills') && resp.ok()),
    page.goto('/'),
  ])
  await expect(page.getByRole('button', { name: 'Submit Application' })).toBeEnabled()
}

async function submitForm(page: Page) {
  await page.getByRole('button', { name: 'Submit Application' }).click()
}

async function fillBasicFields(page: Page, email = 'jane@example.com') {
  await page.getByPlaceholder('Jane Doe').fill('Jane Doe')
  await page.getByPlaceholder('jane@example.com').fill(email)
  await page.getByPlaceholder('0501234567').fill('0501234567')
  await page.getByPlaceholder('Tell us about your experience').fill(
    'I have spent three years building production React applications with component-based architecture.',
  )
}

async function selectTopSkill(page: Page, skillName: string) {
  const topSkillsField = page
    .locator('.n-form-item')
    .filter({ hasText: 'Top skills (expert level)' })
  await topSkillsField.locator('.n-base-selection').click()
  await topSkillsField.locator('input').fill(skillName)
  await page.locator('.n-base-select-option').filter({ hasText: skillName }).first().click()
}

test.beforeEach(async ({ page }) => {
  await waitForFormReady(page)
})

test('shows validation errors when submitting an empty form', async ({ page }) => {
  await submitForm(page)

  const alert = errorAlert(page)
  await expect(alert).toBeVisible()
  await expect(alert).toContainText('The name field is required.')
  await expect(alert).toContainText('The email field is required.')
  await expect(alert).toContainText('The phone number field is required.')
  await expect(alert).toContainText('The top skills field is required.')
  await expect(alert).toContainText('The cover letter field is required.')
  await expect(page.getByText('Application Submitted')).not.toBeVisible()
})

test('shows email validation error for an invalid email', async ({ page }) => {
  await fillBasicFields(page, 'not-an-email')
  await submitForm(page)

  const alert = errorAlert(page)
  await expect(alert).toBeVisible()
  await expect(alert).toContainText('The email field must be a valid email address.')
  await expect(alert).toContainText('The top skills field is required.')
})

test('shows error when required fields are filled but top skills are missing', async ({ page }) => {
  await fillBasicFields(page)
  await submitForm(page)

  const alert = errorAlert(page)
  await expect(alert).toBeVisible()
  await expect(alert).toContainText('The top skills field is required.')
  await expect(alert).not.toContainText('The name field is required.')
  await expect(alert).not.toContainText('The email field is required.')
})

test('shows error when cover letter is missing', async ({ page }) => {
  await page.getByPlaceholder('Jane Doe').fill('Jane Doe')
  await page.getByPlaceholder('jane@example.com').fill('jane@example.com')
  await page.getByPlaceholder('0501234567').fill('0501234567')
  await selectTopSkill(page, 'React')
  await submitForm(page)

  const alert = errorAlert(page)
  await expect(alert).toBeVisible()
  await expect(alert).toContainText('The cover letter field is required.')
  await expect(alert).not.toContainText('The top skills field is required.')
})
