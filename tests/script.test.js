const { add, subtract } = require('../src/script');
test('add', () => {
  expect(add(2, 3)).toBe(5);
});
test('subtract', () => {
  expect(subtract(5, 3)).toBe(2);
});