#
# Written by Solar Designer and placed in the public domain.
# See crypt_private.c for more information.
#
CC = gcc
LD = $(CC)
RM = rm -f
CFLAGS = -Wall -O2 -fomit-frame-pointer -funroll-loops
LDFLAGS = -s
LIBS = -lcrypto

all: crypt_private-test

crypt_private-test: crypt_private-test.o
	$(LD) $(LDFLAGS) $(LIBS) crypt_private-test.o -o $@

crypt_private-test.o: crypt_private.c
	$(CC) -c $(CFLAGS) crypt_private.c -DTEST -o $@

clean:
	$(RM) crypt_private-test*
