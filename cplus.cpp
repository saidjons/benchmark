#include <iostream>
#include <cstdlib>
#include <chrono>
using std::chrono::steady_clock;

void switchValues(int64_t *, int64_t, int64_t);

void quickSort(int64_t *a, int64_t size, int64_t start = 0, int64_t last = -1) {
  int64_t wall = start;
  last = last == -1 ? size - 1 : last;
  if (last - start < 1)
    return;

  switchValues(a, (start+last)/2, last);
  for (int i = start; i < last; ++i)
    if (a[i] < a[last]) { 
      switchValues(a, i, wall); 
      wall++; 
    }

  switchValues(a, wall, last);
  quickSort(a, size, start, wall-1);
  quickSort(a, size, wall+1, last);
}

void switchValues(int64_t *a, int64_t i1, int64_t i2) {
  if (i1 != i2) 
    std::swap(a[i1], a[i2]);
}

int main() {
  int64_t size = 1000000;
  int64_t *a = new int64_t[size];
  for (int64_t i = 0; i < size; ++i)
    a[i] = std::rand();

  steady_clock::time_point begin = steady_clock::now();
  quickSort(a, size);
  steady_clock::time_point end = steady_clock::now();
  std::cout << std::chrono::duration_cast<std::chrono::milliseconds> (end - begin).count() << " ms\n";
  return 0;
}